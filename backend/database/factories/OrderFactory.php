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
        $event = Event::inRandomOrder()->first();
        if (!$event) {
            $event = Event::factory()->create();
        }
        $user = User::inRandomOrder()->first();
        if (!$user) {
            $user = User::factory()->create();
        }
        $tickets_count = fake()->numberBetween(1, $event->available_tickets);
        $total_price = round($event->price * $tickets_count, 2);
        $status = fake()->randomElement(OrderStatusEnum::values());
        return [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'tickets_count' => $tickets_count,
            'total_price' => $total_price,
            'status' => $status,
        ];
    }
}
