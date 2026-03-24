<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use Stripe\StripeClient;

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
            $checkoutSession = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'egp',
                            'unit_amount' => round($order->total_price * 100),
                            'product_data' => ['name' => 'Order #' . $order->id],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'metadata' => $metadata,
                'success_url' => config('services.payment.success_url') . $order->id,
                'cancel_url' => config('services.payment.cancel_url') . $order->id,
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
        $stripe = new StripeClient(config('services.stripe.secret_key'));
        try {
            $refund = $stripe->refunds->create([
                'payment_intent' => $transactionId,
            ]);
            if ($refund->status === 'succeeded') {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}