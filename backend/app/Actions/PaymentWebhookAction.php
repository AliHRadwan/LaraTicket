<?php

namespace App\Actions;

use App\Models\Order;
use App\DTOs\PaymentWebhookDTO;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Mail\OrderCompletedEmail;
use App\Mail\OrderCancelledEmail;
use App\Mail\OrderRefundedEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PaymentWebhookAction
{
    public function handle(PaymentWebhookDTO $paymentWebhookData): object
    {
        return match ($paymentWebhookData->eventType) {
            'checkout.session.completed' => $this->completeOrder($paymentWebhookData),
            'payment_intent.succeeded' => $this->completeOrder($paymentWebhookData),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($paymentWebhookData),
            'checkout.session.expired' => $this->handleCheckoutSessionExpired($paymentWebhookData),
            'charge.refunded' => $this->handleChargeRefunded($paymentWebhookData),
            default => $this->handleUnknownEvent($paymentWebhookData),
        };
    }

    private function completeOrder(PaymentWebhookDTO $dto): object
    {
        try {
            $order = Order::findOrFail($dto->orderId);

            if ($order->status === OrderStatusEnum::COMPLETED) {
                Log::info('Order already completed, skipping duplicate event', ['order_id' => $order->id]);
                return (object) [
                    'success' => true,
                    'message' => 'Order already completed',
                ];
            }

            DB::transaction(function () use ($order, $dto) {
                $order->update(['status' => OrderStatusEnum::COMPLETED->value]);
                $order->payments()->create([
                    'amount' => (float) $dto->paymentAmountInCents / 100,
                    'provider' => $dto->paymentProvider,
                    'provider_transaction_id' => $dto->paymentIntentId ?? $dto->sessionId,
                    'payment_method' => $dto->paymentMethod ?? 'card',
                    'status' => PaymentStatusEnum::PAID->value,
                    'notes' => $dto->paymentNotes,
                ]);
            });

            Log::info('Order completed successfully', ['order_id' => $order->id]);
            Mail::to($order->user->email)->send(new OrderCompletedEmail($order));

            return (object) [
                'success' => true,
                'message' => 'Order completed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Order completion failed', [
                'order_id' => $dto->orderId,
                'error' => $e->getMessage(),
            ]);
            return (object) [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function handlePaymentFailed(PaymentWebhookDTO $dto): object
    {
        try {
            $order = Order::findOrFail($dto->orderId);

            if ($order->status === OrderStatusEnum::CANCELLED) {
                return (object) [
                    'success' => true,
                    'message' => 'Order already cancelled',
                ];
            }

            DB::transaction(function () use ($order, $dto) {
                $order->update(['status' => OrderStatusEnum::CANCELLED->value]);
                $order->payments()->create([
                    'amount' => (float) $dto->paymentAmountInCents / 100,
                    'provider' => $dto->paymentProvider,
                    'provider_transaction_id' => $dto->paymentIntentId,
                    'payment_method' => $dto->paymentMethod,
                    'status' => PaymentStatusEnum::FAILED->value,
                    'notes' => $dto->paymentNotes,
                ]);
            });

            Log::info('Payment failed for order', ['order_id' => $order->id]);
            Mail::to($order->user->email)->send(new OrderCancelledEmail($order));

            return (object) [
                'success' => true,
                'message' => 'Order cancelled due to payment failure',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to handle payment failure', [
                'order_id' => $dto->orderId,
                'error' => $e->getMessage(),
            ]);
            return (object) [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function handleCheckoutSessionExpired(PaymentWebhookDTO $dto): object
    {
        try {
            $order = Order::findOrFail($dto->orderId);

            if ($order->status === OrderStatusEnum::CANCELLED) {
                return (object) [
                    'success' => true,
                    'message' => 'Order already cancelled',
                ];
            }

            $order->update(['status' => OrderStatusEnum::CANCELLED->value]);

            Log::info('Checkout session expired', ['order_id' => $dto->orderId]);
            Mail::to($order->user->email)->send(new OrderCancelledEmail($order));

            return (object) [
                'success' => true,
                'message' => 'Checkout session expired',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to handle checkout session expiry', [
                'order_id' => $dto->orderId,
                'error' => $e->getMessage(),
            ]);
            return (object) [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function handleChargeRefunded(PaymentWebhookDTO $dto): object
    {
        try {
            $order = Order::findOrFail($dto->orderId);

            DB::transaction(function () use ($order, $dto) {
                $order->update(['status' => OrderStatusEnum::REFUNDED->value]);
                $order->payments()->create([
                    'amount' => (float) $dto->paymentAmountInCents / 100,
                    'provider' => $dto->paymentProvider,
                    'provider_transaction_id' => $dto->paymentIntentId,
                    'payment_method' => $dto->paymentMethod,
                    'status' => PaymentStatusEnum::REFUNDED->value,
                    'notes' => $dto->paymentNotes,
                ]);
            });

            Log::info('Charge refunded', ['order_id' => $dto->orderId]);
            Mail::to($order->user->email)->send(new OrderRefundedEmail($order));

            return (object) [
                'success' => true,
                'message' => 'Charge refunded',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to handle charge refund', [
                'order_id' => $dto->orderId,
                'error' => $e->getMessage(),
            ]);
            return (object) [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function handleUnknownEvent(PaymentWebhookDTO $dto): object
    {
        Log::warning("Unhandled Stripe event: {$dto->eventType}");
        return (object) [
            'success' => false,
            'error' => 'Unhandled Stripe event: ' . $dto->eventType,
        ];
    }
}
