<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\Event;
use App\Enums\OrderStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $event = Event::random();
        $user = User::random();
        if (!$user) {
            $user = User::factory()->create();
        }
        if (!$event) {
            $event = Event::factory()->create();
        }
        return [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'tickets_count' => fake()->numberBetween(1, 10),
            'total_price' => $event->price * fake()->numberBetween(1, 10),
            'status' => fake()->randomElement(OrderStatusEnum::cases()),
        ];
    }
}
