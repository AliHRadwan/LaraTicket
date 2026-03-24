<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start_datetime = fake()->dateTime();
        $end_datetime = fake()->dateTimeBetween($start_datetime, '+1 hour');
        $total_tickets = fake()->numberBetween(100, 500);
        $user = User::random();
        if (!$user) {
            $user = User::factory()->create();
        }
        return [
            'title' => fake()->sentence(),
            'slug' => fake()->slug(),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 0, 5000),
            'total_tickets' => $total_tickets,
            'available_tickets' => $total_tickets - fake()->numberBetween(0, $total_tickets),
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'location' => fake()->address(),
            'user_id' => $user->id,
        ];
    }
}
