<?php

namespace App\Actions;

use App\DTOs\NotificationDTO;
use App\DTOs\PaymentWebhookDTO;
use App\Enums\NotificationType;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Mail\OrderCancelledEmail;
use App\Mail\OrderCompletedEmail;
use App\Mail\OrderRefundedEmail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProcessedWebhookEvent;
use App\Notifications\NotificationSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookAction
{
    public function handle(PaymentWebhookDTO $dto): object
    {
        if ($this->isEventAlreadyProcessed($dto->stripeEventId)) {
            Log::notice('Duplicate webhook event skipped', [
                'event_id' => $dto->stripeEventId,
                'event_type' => $dto->eventType,
            ]);
            return (object) [
                'success' => true,
                'message' => 'Event already processed',
            ];
        }

        $result = match ($dto->eventType) {
            'checkout.session.completed' => $this->completeOrder($dto),
            'payment_intent.succeeded' => $this->completeOrder($dto),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($dto),
            'checkout.session.expired' => $this->handleCheckoutSessionExpired($dto),
            'charge.refunded' => $this->handleChargeRefunded($dto),
            default => $this->handleUnknownEvent($dto),
        };

        if ($result->success ?? false) {
            $this->markEventAsProcessed($dto->stripeEventId, $dto->eventType);
        }

        return $result;
    }

    private function isEventAlreadyProcessed(string $eventId): bool
    {
        return ProcessedWebhookEvent::where('event_id', $eventId)->exists();
    }

    private function markEventAsProcessed(string $eventId, string $eventType): void
    {
        ProcessedWebhookEvent::firstOrCreate(
            ['event_id' => $eventId],
            [
                'event_type' => $eventType,
                'processed_at' => now(),
            ]
        );
    }

    private function completeOrder(PaymentWebhookDTO $dto): object
    {
        try {
            $order = Order::findOrFail($dto->orderId);

            if ($order->status === OrderStatusEnum::COMPLETED) {
                Log::notice('Order already completed, skipping', ['order_id' => $order->id]);
                return (object) [
                    'success' => true,
                    'message' => 'Order already completed',
                ];
            }

            $idempotencyKey = 'webhook_payment_' . $dto->stripeEventId;

            DB::transaction(function () use ($order, $dto, $idempotencyKey) {
                $order->update(['status' => OrderStatusEnum::COMPLETED->value]);
                $order->payments()->create([
                    'amount' => (float) $dto->paymentAmountInCents / 100,
                    'provider' => $dto->paymentProvider,
                    'provider_transaction_id' => $dto->paymentIntentId ?? $dto->sessionId,
                    'payment_method' => $dto->paymentMethod ?? 'card',
                    'status' => PaymentStatusEnum::PAID->value,
                    'notes' => $dto->paymentNotes,
                    'idempotency_key' => $idempotencyKey,
                ]);
            });

            Log::info('Order completed', [
                'order_id' => $order->id,
                'user_id' => $dto->userId,
                'amount' => $dto->paymentAmountInCents / 100,
            ]);

            $order->loadMissing(['user', 'event']);

            $order->user->notify(new NotificationSystem(new NotificationDTO(
                type: NotificationType::ORDER_COMPLETED,
                title: "Order #{$order->id} Completed",
                body: "Your payment for {$order->event->title} has been received. {$order->tickets_count} ticket(s) confirmed.",
                mailable: new OrderCompletedEmail($order),
                meta: [
                    'order_id' => $order->id,
                    'event_id' => $order->event_id,
                    'tickets_count' => $order->tickets_count,
                    'total_price' => $order->total_price,
                ],
            )));

            return (object) [
                'success' => true,
                'message' => 'Order completed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Order completion failed', [
                'order_id' => $dto->orderId,
                'stripe_event' => $dto->stripeEventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
                Log::notice('Order already cancelled, skipping', ['order_id' => $order->id]);
                return (object) [
                    'success' => true,
                    'message' => 'Order already cancelled',
                ];
            }

            $idempotencyKey = 'webhook_payment_' . $dto->stripeEventId;

            DB::transaction(function () use ($order, $dto, $idempotencyKey) {
                $order->update(['status' => OrderStatusEnum::CANCELLED->value]);
                $order->payments()->create([
                    'amount' => (float) $dto->paymentAmountInCents / 100,
                    'provider' => $dto->paymentProvider,
                    'provider_transaction_id' => $dto->paymentIntentId,
                    'payment_method' => $dto->paymentMethod,
                    'status' => PaymentStatusEnum::FAILED->value,
                    'notes' => $dto->paymentNotes,
                    'idempotency_key' => $idempotencyKey,
                ]);
            });

            Log::warning('Payment failed for order', [
                'order_id' => $order->id,
                'user_id' => $dto->userId,
                'stripe_event' => $dto->stripeEventId,
            ]);

            $order->loadMissing(['user', 'event']);

            $order->user->notify(new NotificationSystem(new NotificationDTO(
                type: NotificationType::ORDER_CANCELLED,
                title: "Order #{$order->id} Cancelled",
                body: "Your payment for {$order->event->title} could not be processed. The order has been cancelled.",
                mailable: new OrderCancelledEmail($order),
                meta: [
                    'order_id' => $order->id,
                    'event_id' => $order->event_id,
                    'reason' => 'payment_failed',
                ],
            )));

            return (object) [
                'success' => true,
                'message' => 'Order cancelled due to payment failure',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to handle payment failure', [
                'order_id' => $dto->orderId,
                'stripe_event' => $dto->stripeEventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
                Log::notice('Order already cancelled (expired), skipping', ['order_id' => $order->id]);
                return (object) [
                    'success' => true,
                    'message' => 'Order already cancelled',
                ];
            }

            $order->update(['status' => OrderStatusEnum::CANCELLED->value]);

            Log::notice('Checkout session expired', [
                'order_id' => $dto->orderId,
                'user_id' => $dto->userId,
                'stripe_event' => $dto->stripeEventId,
            ]);

            $order->loadMissing(['user', 'event']);

            $order->user->notify(new NotificationSystem(new NotificationDTO(
                type: NotificationType::ORDER_CANCELLED,
                title: "Order #{$order->id} Expired",
                body: "Your checkout session for {$order->event->title} has expired. Please place a new order if you still wish to attend.",
                mailable: new OrderCancelledEmail($order),
                meta: [
                    'order_id' => $order->id,
                    'event_id' => $order->event_id,
                    'reason' => 'session_expired',
                ],
            )));

            return (object) [
                'success' => true,
                'message' => 'Checkout session expired',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to handle checkout session expiry', [
                'order_id' => $dto->orderId,
                'stripe_event' => $dto->stripeEventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

            $refundNote = 'Refund: '.($dto->paymentAmountInCents / 100).' (webhook '.$dto->stripeEventId.')';
            if (! empty($dto->paymentNotes)) {
                $refundNote = trim($dto->paymentNotes).' | '.$refundNote;
            }

            DB::transaction(function () use ($order, $dto, $refundNote) {
                $order->update(['status' => OrderStatusEnum::REFUNDED->value]);

                if (! $dto->paymentIntentId) {
                    Log::warning('Refund webhook: missing payment_intent', [
                        'order_id' => $order->id,
                        'stripe_event' => $dto->stripeEventId,
                    ]);

                    return;
                }

                $payment = Payment::query()
                    ->where('order_id', $order->id)
                    ->where('provider_transaction_id', $dto->paymentIntentId)
                    ->first();

                if ($payment) {
                    $payment->update([
                        'status' => PaymentStatusEnum::REFUNDED->value,
                        'notes' => $this->appendPaymentNote($payment->notes, $refundNote),
                    ]);

                    return;
                }

                Log::warning('Refund webhook: no payment row for payment_intent (order still marked refunded)', [
                    'order_id' => $order->id,
                    'payment_intent' => $dto->paymentIntentId,
                    'stripe_event' => $dto->stripeEventId,
                ]);
            });

            Log::info('Charge refunded', [
                'order_id' => $dto->orderId,
                'user_id' => $dto->userId,
                'amount' => $dto->paymentAmountInCents / 100,
                'stripe_event' => $dto->stripeEventId,
            ]);

            $order->loadMissing(['user', 'event']);

            $order->user->notify(new NotificationSystem(new NotificationDTO(
                type: NotificationType::ORDER_REFUNDED,
                title: "Order #{$order->id} Refunded",
                body: "Your order for {$order->event->title} has been refunded. " . number_format($order->total_price, 2) . " EGP will be returned to your payment method.",
                mailable: new OrderRefundedEmail($order),
                meta: [
                    'order_id' => $order->id,
                    'event_id' => $order->event_id,
                    'refund_amount' => $order->total_price,
                ],
            )));

            return (object) [
                'success' => true,
                'message' => 'Charge refunded',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to handle charge refund', [
                'order_id' => $dto->orderId,
                'stripe_event' => $dto->stripeEventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return (object) [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function appendPaymentNote(?string $existing, string $append): string
    {
        $append = trim($append);
        if ($append === '') {
            return (string) ($existing ?? '');
        }
        if ($existing === null || trim((string) $existing) === '') {
            return $append;
        }

        return trim((string) $existing)."\n".$append;
    }

    private function handleUnknownEvent(PaymentWebhookDTO $dto): object
    {
        Log::warning('Unhandled Stripe event type', [
            'event_type' => $dto->eventType,
            'stripe_event' => $dto->stripeEventId,
            'order_id' => $dto->orderId,
        ]);

        return (object) [
            'success' => false,
            'error' => 'Unhandled Stripe event: ' . $dto->eventType,
        ];
    }
}
