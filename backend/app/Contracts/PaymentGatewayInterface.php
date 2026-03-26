<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGatewayInterface
{
    public function processPayment(Order $order, array $metadata = []): object;

    /**
     * @return object{success: bool, message: ?string, refund_id: ?string}
     */
    public function refundPayment(string $transactionId, int|null $amountInCents = null): object;

    public function verifyWebhookPayload(string $payload, string $sigHeader): ?object;
}
