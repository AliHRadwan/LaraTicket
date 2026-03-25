<?php

namespace Database\Factories;

use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => round(fake()->randomFloat(2, 10, 1000), 2),
            'provider' => 'stripe',
            'provider_transaction_id' => 'pi_' . Str::random(24),
            'payment_method' => 'card',
            'status' => PaymentStatusEnum::PENDING->value,
            'notes' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => PaymentStatusEnum::PAID->value]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => PaymentStatusEnum::FAILED->value]);
    }

    public function refunded(): static
    {
        return $this->state(fn () => ['status' => PaymentStatusEnum::REFUNDED->value]);
    }
}
