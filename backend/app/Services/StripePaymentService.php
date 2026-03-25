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
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
                'success_url' => config('services.payment.success_url') . '/' . $order->id,
                'cancel_url' => config('services.payment.cancel_url') . '/' . $order->id,
            ]);

            return $checkoutSession;
        } catch (\Exception $e) {
            return (object) [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function refundPayment(string $transactionId, int|null $amountInCents = null): bool
    {
        try {
            $params = ['payment_intent' => $transactionId];

            if ($amountInCents !== null) {
                $params['amount'] = $amountInCents;
            }

            $refund = $this->stripe->refunds->create($params);

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
