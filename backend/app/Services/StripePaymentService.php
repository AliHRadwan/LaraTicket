<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentService implements PaymentGatewayInterface
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret_key'));
    }

    public function processPayment(Order $order, array $metadata = []): object
    {
        try {
            $metadata = array_merge([
                'order_id' => (string) $order->id,
                'user_id' => (string) $order->user_id,
            ], $metadata);

            ksort($metadata);

            $idempotencyKey = $this->checkoutSessionIdempotencyKey($order, $metadata);

            $checkoutSession = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'customer_email' => $order->user->email,
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'egp',
                            'unit_amount' => (int) round($order->total_price * 100),
                            'product_data' => ['name' => 'Order #' . $order->id],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'metadata' => $metadata,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
                'success_url' => config('services.payment.success_url') . '/' . $order->id,
                'cancel_url' => config('services.payment.cancel_url') . '/' . $order->id,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            return $checkoutSession;
        } catch (\Exception $e) {
            return (object) [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Stripe idempotency keys must only be reused for byte-identical requests.
     * Fingerprint amount, URLs, email, and metadata so a changed order gets a new key.
     */
    private function checkoutSessionIdempotencyKey(Order $order, array $metadata): string
    {
        $unitAmount = (int) round($order->total_price * 100);
        $email = $order->user?->email ?? '';
        $successUrl = config('services.payment.success_url').'/'.$order->id;
        $cancelUrl = config('services.payment.cancel_url').'/'.$order->id;

        $fingerprint = hash('sha256', implode("\0", [
            (string) $order->id,
            (string) $order->user_id,
            (string) $unitAmount,
            'egp',
            $email,
            $successUrl,
            $cancelUrl,
            json_encode($metadata, JSON_THROW_ON_ERROR),
        ]));

        return 'checkout_order_'.$order->id.'_'.substr($fingerprint, 0, 32);
    }

    public function refundPayment(string $transactionId, int|null $amountInCents = null): bool
    {
        try {
            $params = ['payment_intent' => $transactionId];

            if ($amountInCents !== null) {
                $params['amount'] = $amountInCents;
            }

            $idempotencyKey = 'refund_' . $transactionId . '_' . ($amountInCents ?? 'full');

            $refund = $this->stripe->refunds->create($params, [
                'idempotency_key' => $idempotencyKey,
            ]);

            return $refund->status === 'succeeded';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function verifyWebhookPayload(string $payload, string $sigHeader): ?object
    {
        try {
            return Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            return null;
        }
    }
}
