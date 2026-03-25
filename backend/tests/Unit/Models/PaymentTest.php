<?php

use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use App\Models\Payment;

test('payment has correct fillable attributes', function () {
    $payment = new Payment();

    expect($payment->getFillable())->toBe([
        'order_id', 'amount', 'provider', 'provider_transaction_id',
        'payment_method', 'status', 'notes', 'idempotency_key',
    ]);
});

test('payment status is cast to enum', function () {
    $payment = Payment::factory()->create();

    expect($payment->status)->toBeInstanceOf(PaymentStatusEnum::class);
});

test('payment belongs to an order', function () {
    $payment = Payment::factory()->create();

    expect($payment->order())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($payment->order)->toBeInstanceOf(Order::class);
});

test('payment factory paid state works', function () {
    $payment = Payment::factory()->paid()->create();

    expect($payment->status)->toBe(PaymentStatusEnum::PAID);
});

test('payment factory failed state works', function () {
    $payment = Payment::factory()->failed()->create();

    expect($payment->status)->toBe(PaymentStatusEnum::FAILED);
});
