<?php

use App\Enums\OrderStatusEnum;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

test('order has correct fillable attributes', function () {
    $order = new Order();

    expect($order->getFillable())->toBe([
        'user_id', 'event_id', 'tickets_count', 'total_price', 'status',
    ]);
});

test('order status is cast to enum', function () {
    $order = Order::factory()->create();

    expect($order->status)->toBeInstanceOf(OrderStatusEnum::class);
});

test('order belongs to a user', function () {
    $order = Order::factory()->create();

    expect($order->user)->toBeInstanceOf(User::class);
});

test('order belongs to an event', function () {
    $order = Order::factory()->create();

    expect($order->event)->toBeInstanceOf(Event::class);
});

test('order has many payments', function () {
    $order = Order::factory()->create();
    Payment::factory()->count(2)->create(['order_id' => $order->id]);

    expect($order->payments())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($order->payments)->toHaveCount(2);
});

test('order factory completed state works', function () {
    $order = Order::factory()->completed()->create();

    expect($order->status)->toBe(OrderStatusEnum::COMPLETED);
});

test('order factory cancelled state works', function () {
    $order = Order::factory()->cancelled()->create();

    expect($order->status)->toBe(OrderStatusEnum::CANCELLED);
});

test('order factory refunded state works', function () {
    $order = Order::factory()->refunded()->create();

    expect($order->status)->toBe(OrderStatusEnum::REFUNDED);
});
