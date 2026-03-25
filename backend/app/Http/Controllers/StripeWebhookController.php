<?php

namespace App\Http\Controllers;

use App\Actions\PaymentWebhookAction;
use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentWebhookDTO;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly PaymentWebhookAction $webhookAction,
    ) {}

    public function handleWebhook(Request $request): JsonResponse
    {
        $stripeEvent = $this->paymentGateway->verifyWebhookPayload(
            $request->getContent(),
            $request->header('Stripe-Signature', '')
        );

        if (! $stripeEvent) {
            Log::warning('Stripe webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid webhook signature.'], 400);
        }

        Log::info('Stripe webhook received', [
            'event_id' => $stripeEvent->id,
            'event_type' => $stripeEvent->type,
        ]);

        $dto = $this->buildDTO($stripeEvent);

        if (! $dto) {
            Log::notice('Webhook event ignored (no order context)', [
                'event_id' => $stripeEvent->id,
                'event_type' => $stripeEvent->type,
            ]);

            return response()->json(['message' => 'Event acknowledged but ignored (no order context).'], 200);
        }

        $result = $this->webhookAction->handle($dto);

        $statusCode = ($result->success ?? false) ? 200 : 500;

        if (! ($result->success ?? false)) {
            Log::error('Webhook action failed', [
                'event_id' => $stripeEvent->id,
                'event_type' => $stripeEvent->type,
                'error' => $result->error ?? 'Unknown',
            ]);
        }

        return response()->json($result, $statusCode);
    }

    private function buildDTO(object $stripeEvent): ?PaymentWebhookDTO
    {
        $eventObject = $stripeEvent->data->object;

        $orderId = $eventObject->metadata->order_id ?? null;
        $userId = $eventObject->metadata->user_id ?? null;

        if (! $orderId && isset($eventObject->payment_intent) && is_string($eventObject->payment_intent)) {
            $payment = Payment::where('provider_transaction_id', $eventObject->payment_intent)->first();
            if ($payment) {
                $orderId = $payment->order_id;
                $userId = $payment->order->user_id;

                Log::debug('Order context resolved from payment record', [
                    'payment_intent' => $eventObject->payment_intent,
                    'order_id' => $orderId,
                ]);
            }
        }

        if (! $orderId || ! $userId) {
            Log::warning('Webhook event missing order context', [
                'event_id' => $stripeEvent->id,
                'event_type' => $stripeEvent->type,
            ]);
            return null;
        }

        $amount = $eventObject->amount_total
            ?? $eventObject->amount
            ?? $eventObject->amount_refunded
            ?? 0;

        $isSessionEvent = in_array($stripeEvent->type, [
            'checkout.session.completed',
            'checkout.session.expired',
        ]);

        $paymentIntentId = null;
        if (isset($eventObject->payment_intent) && is_string($eventObject->payment_intent)) {
            $paymentIntentId = $eventObject->payment_intent;
        } elseif (! $isSessionEvent && isset($eventObject->id)) {
            $paymentIntentId = $eventObject->id;
        }

        return new PaymentWebhookDTO(
            orderId: (int) $orderId,
            userId: (int) $userId,
            paymentAmountInCents: (int) $amount,
            eventType: $stripeEvent->type,
            stripeEventId: $stripeEvent->id,
            paymentMethod: $eventObject->payment_method_types[0] ?? null,
            paymentIntentId: $paymentIntentId,
            sessionId: $isSessionEvent ? $eventObject->id : null,
        );
    }
}
