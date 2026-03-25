<?php

use App\Enums\NotificationType;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;

// --- OrderStatusEnum ---

test('order status enum has all expected cases', function () {
    expect(OrderStatusEnum::cases())->toHaveCount(4);
    expect(OrderStatusEnum::PENDING->value)->toBe('pending');
    expect(OrderStatusEnum::COMPLETED->value)->toBe('completed');
    expect(OrderStatusEnum::CANCELLED->value)->toBe('cancelled');
    expect(OrderStatusEnum::REFUNDED->value)->toBe('refunded');
});

test('order status values() returns flat array', function () {
    $values = OrderStatusEnum::values();

    expect($values)->toBe(['pending', 'completed', 'cancelled', 'refunded']);
});

test('order status label() returns human-readable strings', function () {
    expect(OrderStatusEnum::PENDING->label())->toBe('Pending');
    expect(OrderStatusEnum::COMPLETED->label())->toBe('Completed');
    expect(OrderStatusEnum::CANCELLED->label())->toBe('Cancelled');
    expect(OrderStatusEnum::REFUNDED->label())->toBe('Refunded');
});

test('order status color() returns valid color strings', function () {
    expect(OrderStatusEnum::PENDING->color())->toBe('warning');
    expect(OrderStatusEnum::COMPLETED->color())->toBe('success');
    expect(OrderStatusEnum::CANCELLED->color())->toBe('danger');
    expect(OrderStatusEnum::REFUNDED->color())->toBe('info');
});

// --- PaymentStatusEnum ---

test('payment status enum has all expected cases', function () {
    expect(PaymentStatusEnum::cases())->toHaveCount(5);
    expect(PaymentStatusEnum::PENDING->value)->toBe('pending');
    expect(PaymentStatusEnum::PAID->value)->toBe('paid');
    expect(PaymentStatusEnum::FAILED->value)->toBe('failed');
    expect(PaymentStatusEnum::CANCELLED->value)->toBe('cancelled');
    expect(PaymentStatusEnum::REFUNDED->value)->toBe('refunded');
});

test('payment status label() returns human-readable strings', function () {
    expect(PaymentStatusEnum::PAID->label())->toBe('Paid');
    expect(PaymentStatusEnum::FAILED->label())->toBe('Failed');
});

test('payment status color() returns valid color strings', function () {
    expect(PaymentStatusEnum::PAID->color())->toBe('success');
    expect(PaymentStatusEnum::FAILED->color())->toBe('danger');
    expect(PaymentStatusEnum::PENDING->color())->toBe('warning');
});

// --- NotificationType ---

test('notification type enum has all expected cases', function () {
    expect(NotificationType::cases())->toHaveCount(5);
    expect(NotificationType::ORDER_PLACED->value)->toBe('order_placed');
    expect(NotificationType::ORDER_COMPLETED->value)->toBe('order_completed');
    expect(NotificationType::ORDER_CANCELLED->value)->toBe('order_cancelled');
    expect(NotificationType::ORDER_REFUNDED->value)->toBe('order_refunded');
    expect(NotificationType::VERIFY_EMAIL->value)->toBe('verify_email');
});

test('notification type title() returns human-readable strings', function () {
    expect(NotificationType::ORDER_PLACED->title())->toBe('Order Placed');
    expect(NotificationType::VERIFY_EMAIL->title())->toBe('Verify Email');
});
