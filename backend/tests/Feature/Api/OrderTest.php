<?php

use App\Contracts\PaymentGatewayInterface;
use App\Enums\OrderStatusEnum;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    mockPaymentGateway();
});

function mockPaymentGateway(object $result = null): void
{
    $mock = Mockery::mock(PaymentGatewayInterface::class);
    $mock->shouldReceive('processPayment')
        ->andReturn($result ?? (object) ['url' => 'https://checkout.stripe.com/test', 'id' => 'cs_test_123']);
    $mock->shouldReceive('refundPayment')->andReturn((object) [
        'success' => true,
        'message' => null,
        'refund_id' => 're_test_123',
    ]);
    app()->instance(PaymentGatewayInterface::class, $mock);
}

test('authenticated user can list their orders', function () {
    $user = User::factory()->create();
    Order::factory()->count(3)->create(['user_id' => $user->id]);
    Order::factory()->count(2)->create(); // other users

    $response = $this->actingAs($user)->getJson('/api/orders');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

test('admin can list all orders', function () {
    $admin = User::factory()->admin()->create();
    Order::factory()->count(5)->create();

    $response = $this->actingAs($admin)->getJson('/api/orders');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
});

test('unauthenticated user cannot list orders', function () {
    $response = $this->getJson('/api/orders');

    $response->assertStatus(401);
});

test('orders listing is paginated', function () {
    $user = User::factory()->create();
    Order::factory()->count(20)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/orders?per_page=5');

    expect($response->json('data'))->toHaveCount(5);
    expect($response->json('total'))->toBe(20);
});

test('user can create an order with valid data', function () {
    mockPaymentGateway();

    $user = User::factory()->create();
    $event = Event::factory()->create(['price' => 100, 'available_tickets' => 50]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'event_id' => $event->id,
        'tickets_count' => 2,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'order', 'checkout_url']);

    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'tickets_count' => 2,
        'total_price' => 200.00,
        'status' => 'pending',
    ]);
});

test('order creation decrements available tickets', function () {
    mockPaymentGateway();

    $user = User::factory()->create();
    $event = Event::factory()->create(['available_tickets' => 50, 'total_tickets' => 50]);

    $this->actingAs($user)->postJson('/api/orders', [
        'event_id' => $event->id,
        'tickets_count' => 3,
    ]);

    expect($event->fresh()->available_tickets)->toBe(47);
});

test('cannot order more tickets than available', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['available_tickets' => 2]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'event_id' => $event->id,
        'tickets_count' => 5,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('tickets_count');
});

test('order creation handles payment gateway failure gracefully', function () {
    mockPaymentGateway((object) ['success' => false, 'message' => 'Gateway down']);
    // Re-mock with failure response

    $user = User::factory()->create();
    $event = Event::factory()->create(['available_tickets' => 50, 'total_tickets' => 50]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'event_id' => $event->id,
        'tickets_count' => 2,
    ]);

    $response->assertStatus(502);

    // Tickets should be restored
    expect($event->fresh()->available_tickets)->toBe(50);

    // Order should be cancelled
    $order = Order::latest()->first();
    expect($order->status)->toBe(OrderStatusEnum::CANCELLED);
});

test('user can view their own order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson("/api/orders/{$order->id}");

    $response->assertOk()
        ->assertJsonPath('order.id', $order->id);
});

test('user cannot view another users order', function () {
    $user = User::factory()->create();
    $otherOrder = Order::factory()->create();

    $response = $this->actingAs($user)->getJson("/api/orders/{$otherOrder->id}");

    $response->assertStatus(403);
});

test('admin can view any order', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create();

    $response = $this->actingAs($admin)->getJson("/api/orders/{$order->id}");

    $response->assertOk();
});

test('admin can update order status', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create();

    $response = $this->actingAs($admin)->putJson("/api/orders/{$order->id}", [
        'status' => 'cancelled',
    ]);

    $response->assertOk();
    expect($order->fresh()->status)->toBe(OrderStatusEnum::CANCELLED);
});

test('order creation requires event_id and tickets_count', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/orders', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['event_id', 'tickets_count']);
});
