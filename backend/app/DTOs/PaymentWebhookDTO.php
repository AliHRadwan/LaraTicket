<?php

namespace App\DTOs;

readonly class PaymentWebhookDTO
{
    public function __construct(
        public int $orderId,
        public int $userId,
        public int $paymentAmountInCents,
        public string $eventType,
        public string $paymentProvider = 'stripe',
        public ?string $paymentMethod = null,
        public ?string $paymentIntentId = null,
        public ?string $sessionId = null,
        public ?string $paymentNotes = null,
    ) {}
}
