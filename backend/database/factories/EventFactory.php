<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
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
        $available_tickets = $total_tickets - fake()->numberBetween(0, $total_tickets);
        $user = User::inRandomOrder()->first();
        if (!$user) {
            $user = User::factory()->create();
        }
        return [
            'title' => fake()->sentence(),
            'slug' => Str::slug(fake()->sentence()),
            'description' => fake()->text(),
            'price' => round(fake()->randomFloat(2, 0, 5000), 2),
            'total_tickets' => $total_tickets,
            'available_tickets' => $available_tickets,
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'location' => fake()->address(),
            'user_id' => $user->id,
        ];
    }
}
