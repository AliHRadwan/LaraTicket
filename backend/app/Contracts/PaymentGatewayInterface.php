<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGatewayInterface
{
    public function processPayment(Order $order, array $metadata = []): string;
    public function refundPayment(string $transactionId, int|null $amountInCents = null): bool;
}