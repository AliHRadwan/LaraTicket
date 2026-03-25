<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGatewayInterface
{
    public function processPayment(Order $order, array $metadata = []): object;

    public function refundPayment(string $transactionId, int|null $amountInCents = null): bool;

    public function verifyWebhookPayload(string $payload, string $sigHeader): ?object;
}
