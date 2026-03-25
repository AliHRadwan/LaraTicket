<?php

use App\DTOs\NotificationDTO;
use App\DTOs\PaymentWebhookDTO;
use App\Enums\NotificationType;

// --- PaymentWebhookDTO ---

test('payment webhook dto constructs with required parameters', function () {
    $dto = new PaymentWebhookDTO(
        orderId: 1,
        userId: 2,
        paymentAmountInCents: 5000,
        eventType: 'checkout.session.completed',
        stripeEventId: 'evt_123',
    );

    expect($dto->orderId)->toBe(1);
    expect($dto->userId)->toBe(2);
    expect($dto->paymentAmountInCents)->toBe(5000);
    expect($dto->eventType)->toBe('checkout.session.completed');
    expect($dto->stripeEventId)->toBe('evt_123');
    expect($dto->paymentProvider)->toBe('stripe');
    expect($dto->paymentMethod)->toBeNull();
    expect($dto->paymentIntentId)->toBeNull();
    expect($dto->sessionId)->toBeNull();
    expect($dto->paymentNotes)->toBeNull();
});

test('payment webhook dto accepts optional parameters', function () {
    $dto = new PaymentWebhookDTO(
        orderId: 1,
        userId: 2,
        paymentAmountInCents: 5000,
        eventType: 'checkout.session.completed',
        stripeEventId: 'evt_456',
        paymentMethod: 'card',
        paymentIntentId: 'pi_abc',
        sessionId: 'cs_xyz',
        paymentNotes: 'Test note',
    );

    expect($dto->paymentMethod)->toBe('card');
    expect($dto->paymentIntentId)->toBe('pi_abc');
    expect($dto->sessionId)->toBe('cs_xyz');
    expect($dto->paymentNotes)->toBe('Test note');
});

test('payment webhook dto is readonly', function () {
    $dto = new PaymentWebhookDTO(
        orderId: 1,
        userId: 2,
        paymentAmountInCents: 5000,
        eventType: 'test',
        stripeEventId: 'evt_789',
    );

    $reflection = new ReflectionClass($dto);
    expect($reflection->isReadOnly())->toBeTrue();
});

// --- NotificationDTO ---

test('notification dto constructs with defaults', function () {
    $dto = new NotificationDTO(
        type: NotificationType::ORDER_COMPLETED,
        title: 'Order Completed',
        body: 'Your order is done.',
    );

    expect($dto->type)->toBe(NotificationType::ORDER_COMPLETED);
    expect($dto->title)->toBe('Order Completed');
    expect($dto->body)->toBe('Your order is done.');
    expect($dto->channels)->toBe(['mail', 'database']);
    expect($dto->mailable)->toBeNull();
    expect($dto->actionUrl)->toBeNull();
    expect($dto->actionText)->toBeNull();
    expect($dto->meta)->toBe([]);
});

test('notification dto accepts custom channels', function () {
    $dto = new NotificationDTO(
        type: NotificationType::VERIFY_EMAIL,
        title: 'Verify',
        body: 'Verify email.',
        channels: ['mail'],
    );

    expect($dto->channels)->toBe(['mail']);
});

test('notification dto accepts meta data', function () {
    $dto = new NotificationDTO(
        type: NotificationType::ORDER_PLACED,
        title: 'Placed',
        body: 'Order placed.',
        meta: ['order_id' => 42, 'amount' => 100],
    );

    expect($dto->meta)->toBe(['order_id' => 42, 'amount' => 100]);
});
