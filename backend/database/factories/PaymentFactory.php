<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Order;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $order = Order::inRandomOrder()->first();
        if (!$order) {
            $order = Order::factory()->create();
        }
        return [
            'order_id' => $order->id,
            'amount' => fake()->randomFloat(2, 1, 1000),
            'provider' => 'stripe',
            'provider_transaction_id' => Str::uuid()->toString(),
            'payment_method' => 'card',
            'status' => fake()->randomElement(PaymentStatusEnum::values()),
            'notes' => fake()->text(),
        ];
    }
}
