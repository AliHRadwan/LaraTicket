<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

test('admin can list all payments', function () {
    $admin = User::factory()->admin()->create();
    Payment::factory()->count(3)->create();

    $response = $this->actingAs($admin)->getJson('/api/payments');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

test('non-admin cannot list payments', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/payments');

    $response->assertStatus(403);
});

test('unauthenticated user cannot list payments', function () {
    $response = $this->getJson('/api/payments');

    $response->assertStatus(401);
});

test('admin can view a specific payment', function () {
    $admin = User::factory()->admin()->create();
    $payment = Payment::factory()->create();

    $response = $this->actingAs($admin)->getJson("/api/payments/{$payment->id}");

    $response->assertOk()
        ->assertJsonPath('payment.id', $payment->id);
});

test('user can view payment for their own order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    $payment = Payment::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($user)->getJson("/api/payments/{$payment->id}");

    $response->assertOk();
});

test('user cannot view payment for another users order', function () {
    $user = User::factory()->create();
    $payment = Payment::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/payments/{$payment->id}");

    $response->assertStatus(403);
});

test('payments listing is paginated', function () {
    $admin = User::factory()->admin()->create();
    Payment::factory()->count(20)->create();

    $response = $this->actingAs($admin)->getJson('/api/payments?per_page=5');

    expect($response->json('data'))->toHaveCount(5);
    expect($response->json('total'))->toBe(20);
});
