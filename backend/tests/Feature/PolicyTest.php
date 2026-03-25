<?php

use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

// --- Event Policy ---

test('anyone can view events', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    expect($user->can('view', $event))->toBeTrue();
    expect($user->can('viewAny', Event::class))->toBeTrue();
});

test('admin can create events', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('create', Event::class))->toBeTrue();
});

test('regular user cannot create events', function () {
    $user = User::factory()->create();

    expect($user->can('create', Event::class))->toBeFalse();
});

test('admin can update any event', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    expect($admin->can('update', $event))->toBeTrue();
});

test('event owner can update their event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    expect($user->can('update', $event))->toBeTrue();
});

test('non-owner non-admin cannot update event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    expect($user->can('update', $event))->toBeFalse();
});

test('admin can delete any event', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create();

    expect($admin->can('delete', $event))->toBeTrue();
});

test('non-owner non-admin cannot delete event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    expect($user->can('delete', $event))->toBeFalse();
});

// --- Order Policy ---

test('any authenticated user can create orders', function () {
    $user = User::factory()->create();

    expect($user->can('create', Order::class))->toBeTrue();
});

test('user can view their own order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $order))->toBeTrue();
});

test('user cannot view another users order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create();

    expect($user->can('view', $order))->toBeFalse();
});

test('admin can view any order', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create();

    expect($admin->can('view', $order))->toBeTrue();
});

test('only admin can update orders', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    expect($admin->can('update', $order))->toBeTrue();
    expect($user->can('update', $order))->toBeFalse();
});

// --- Payment Policy ---

test('admin can list payments', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('viewAny', Payment::class))->toBeTrue();
});

test('non-admin cannot list payments', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Payment::class))->toBeFalse();
});

test('admin can view any payment', function () {
    $admin = User::factory()->admin()->create();
    $payment = Payment::factory()->create();

    expect($admin->can('view', $payment))->toBeTrue();
});

test('user can view payment for their own order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->create(['order_id' => $order->id]);

    expect($user->can('view', $payment))->toBeTrue();
});

test('user cannot view payment for another users order', function () {
    $user = User::factory()->create();
    $payment = Payment::factory()->create();

    expect($user->can('view', $payment))->toBeFalse();
});
