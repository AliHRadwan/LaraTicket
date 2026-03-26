<?php

use App\Contracts\PaymentGatewayInterface;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Event;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProcessedWebhookEvent;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

function mockWebhookGateway(?object $stripeEvent): void
{
    $mock = Mockery::mock(PaymentGatewayInterface::class);
    $mock->shouldReceive('verifyWebhookPayload')->andReturn($stripeEvent);
    $mock->shouldReceive('processPayment')->andReturn((object) ['url' => 'https://test.com']);
    $mock->shouldReceive('refundPayment')->andReturn(true);
    app()->instance(PaymentGatewayInterface::class, $mock);
}

function fakeStripeEvent(string $type, int $orderId, int $userId, array $extra = []): object
{
    return (object) [
        'id' => 'evt_' . uniqid(),
        'type' => $type,
        'data' => (object) [
            'object' => (object) array_merge([
                'id' => 'cs_test_' . uniqid(),
                'metadata' => (object) [
                    'order_id' => (string) $orderId,
                    'user_id' => (string) $userId,
                ],
                'amount_total' => 10000,
                'payment_intent' => 'pi_test_' . uniqid(),
                'payment_method_types' => ['card'],
            ], $extra),
        ],
    ];
}

test('webhook rejects invalid signature', function () {
    mockWebhookGateway(null);

    $response = $this->postJson('/api/stripe/webhook', [], [
        'Stripe-Signature' => 'invalid',
    ]);

    $response->assertStatus(400)
        ->assertJson(['error' => 'Invalid webhook signature.']);
});

test('webhook handles checkout.session.completed', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'status' => OrderStatusEnum::PENDING->value,
        'total_price' => 100,
    ]);

    $stripeEvent = fakeStripeEvent('checkout.session.completed', $order->id, $user->id);
    mockWebhookGateway($stripeEvent);

    $response = $this->postJson('/api/stripe/webhook', [], [
        'Stripe-Signature' => 'valid',
    ]);

    $response->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::COMPLETED);
    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'status' => PaymentStatusEnum::PAID->value,
    ]);
});

test('webhook handles payment_intent.payment_failed', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatusEnum::PENDING->value,
    ]);

    $stripeEvent = fakeStripeEvent('payment_intent.payment_failed', $order->id, $user->id);
    mockWebhookGateway($stripeEvent);

    $response = $this->postJson('/api/stripe/webhook', [], [
        'Stripe-Signature' => 'valid',
    ]);

    $response->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::CANCELLED);
    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'status' => PaymentStatusEnum::FAILED->value,
    ]);
});

test('webhook handles charge.refunded', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $order = Order::factory()->completed()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);

    $paymentIntentId = 'pi_test_refund_match';
    Payment::factory()->paid()->create([
        'order_id' => $order->id,
        'provider_transaction_id' => $paymentIntentId,
        'amount' => 50,
    ]);

    $stripeEvent = fakeStripeEvent('charge.refunded', $order->id, $user->id, [
        'payment_intent' => $paymentIntentId,
        'amount_refunded' => 5000,
        'amount_total' => null,
    ]);
    mockWebhookGateway($stripeEvent);

    $response = $this->postJson('/api/stripe/webhook', [], [
        'Stripe-Signature' => 'valid',
    ]);

    $response->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::REFUNDED);
    expect($order->payments()->count())->toBe(1);
    $payment = $order->payments()->first();
    expect($payment->status)->toBe(PaymentStatusEnum::REFUNDED);
    expect($payment->provider_transaction_id)->toBe($paymentIntentId);
});

test('webhook handles checkout.session.expired', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatusEnum::PENDING->value,
    ]);

    $stripeEvent = fakeStripeEvent('checkout.session.expired', $order->id, $user->id);
    mockWebhookGateway($stripeEvent);

    $response = $this->postJson('/api/stripe/webhook', [], [
        'Stripe-Signature' => 'valid',
    ]);

    $response->assertOk();
    expect($order->fresh()->status)->toBe(OrderStatusEnum::CANCELLED);
});

test('webhook skips duplicate events (idempotency)', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatusEnum::PENDING->value,
    ]);

    $stripeEvent = fakeStripeEvent('checkout.session.completed', $order->id, $user->id);
    mockWebhookGateway($stripeEvent);

    // First call processes the event
    $this->postJson('/api/stripe/webhook', [], ['Stripe-Signature' => 'valid']);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::COMPLETED);

    // Second call with same event ID should be idempotent
    $response = $this->postJson('/api/stripe/webhook', [], ['Stripe-Signature' => 'valid']);

    $response->assertOk()
        ->assertJson(['message' => 'Event already processed']);

    // Only one payment record should exist
    expect($order->payments()->count())->toBe(1);
});

test('webhook ignores events without order context', function () {
    $stripeEvent = (object) [
        'id' => 'evt_no_context',
        'type' => 'checkout.session.completed',
        'data' => (object) [
            'object' => (object) [
                'id' => 'cs_test',
                'metadata' => (object) [],
                'amount_total' => 1000,
                'payment_method_types' => ['card'],
            ],
        ],
    ];

    mockWebhookGateway($stripeEvent);

    $response = $this->postJson('/api/stripe/webhook', [], ['Stripe-Signature' => 'valid']);

    $response->assertOk()
        ->assertJson(['message' => 'Event acknowledged but ignored (no order context).']);
});

test('webhook sends notification on order completion', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatusEnum::PENDING->value,
    ]);

    $stripeEvent = fakeStripeEvent('checkout.session.completed', $order->id, $user->id);
    mockWebhookGateway($stripeEvent);

    $this->postJson('/api/stripe/webhook', [], ['Stripe-Signature' => 'valid']);

    Notification::assertSentTo($user, \App\Notifications\NotificationSystem::class);
});

test('processed webhook event is recorded', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatusEnum::PENDING->value,
    ]);

    $stripeEvent = fakeStripeEvent('checkout.session.completed', $order->id, $user->id);
    mockWebhookGateway($stripeEvent);

    $this->postJson('/api/stripe/webhook', [], ['Stripe-Signature' => 'valid']);

    expect(ProcessedWebhookEvent::count())->toBe(1);
    expect(ProcessedWebhookEvent::first()->event_id)->toBe($stripeEvent->id);
});
