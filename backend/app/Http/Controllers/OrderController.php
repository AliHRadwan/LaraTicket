<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\NotificationDTO;
use App\Enums\NotificationType;
use App\Enums\OrderStatusEnum;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Event;
use App\Models\Order;
use App\Notifications\NotificationSystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 15), 50);

        $query = Order::with([
            'event:id,title,start_datetime,location',
            'user:id,name,email',
            'payments:id,order_id,amount,status,created_at',
        ]);

        if (! ($request->user()->is_admin ?? false)) {
            $query->where('user_id', $request->user()->id);
        }

        $orders = $query->latest()->paginate($perPage);

        return response()->json($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $event = Event::findOrFail($validated['event_id']);

        $order = DB::transaction(function () use ($request, $validated, $event) {
            $event->decrement('available_tickets', $validated['tickets_count']);

            return Order::create([
                'user_id' => $request->user()->id,
                'event_id' => $event->id,
                'tickets_count' => $validated['tickets_count'],
                'total_price' => $event->price * $validated['tickets_count'],
                'status' => OrderStatusEnum::PENDING->value,
            ]);
        });

        Log::info('Order created', [
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'event_id' => $event->id,
            'tickets' => $validated['tickets_count'],
            'total_price' => $order->total_price,
        ]);

        $checkoutSession = $this->paymentGateway->processPayment($order);

        if (isset($checkoutSession->success) && $checkoutSession->success === false) {
            $order->update(['status' => OrderStatusEnum::CANCELLED->value]);
            $event->increment('available_tickets', $validated['tickets_count']);

            Log::error('Payment initiation failed', [
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'error' => $checkoutSession->message ?? 'Unknown',
            ]);

            return response()->json([
                'message' => 'Failed to initiate payment. Please try again.',
                'error' => $checkoutSession->message ?? 'Payment gateway error',
            ], 502);
        }

        Log::info('Stripe checkout session created', [
            'order_id' => $order->id,
            'session_id' => $checkoutSession->id ?? null,
        ]);

        $request->user()->notify(new NotificationSystem(new NotificationDTO(
            type: NotificationType::ORDER_PLACED,
            title: "Order #{$order->id} Placed",
            body: "Your order for {$event->title} ({$order->tickets_count} ticket(s)) has been created. Please complete the payment.",
            channels: ['database'],
            meta: [
                'order_id' => $order->id,
                'event_id' => $event->id,
                'tickets_count' => $order->tickets_count,
                'total_price' => $order->total_price,
            ],
        )));

        return response()->json([
            'message' => 'Order created. Complete payment to confirm your tickets.',
            'order' => $order->load('event:id,title'),
            'checkout_url' => $checkoutSession->url,
        ], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json([
            'order' => $order->load(['event', 'user:id,name,email', 'payments']),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $previousStatus = $order->status;
        $order->update($request->validated());

        Log::info('Order status updated', [
            'order_id' => $order->id,
            'admin_id' => $request->user()->id,
            'from' => $previousStatus->value,
            'to' => $request->validated()['status'],
        ]);

        return response()->json([
            'message' => 'Order updated successfully.',
            'order' => $order->fresh()->load(['event:id,title', 'payments:id,order_id,status']),
        ]);
    }
}
