<?php

namespace Database\Factories;

use App\Enums\OrderStatusEnum;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $ticketsCount = fake()->numberBetween(1, 5);

        return [
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'tickets_count' => $ticketsCount,
            'total_price' => round(fake()->randomFloat(2, 50, 500) * $ticketsCount, 2),
            'status' => OrderStatusEnum::PENDING->value,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => OrderStatusEnum::COMPLETED->value]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => OrderStatusEnum::CANCELLED->value]);
    }

    public function refunded(): static
    {
        return $this->state(fn () => ['status' => OrderStatusEnum::REFUNDED->value]);
    }
}
