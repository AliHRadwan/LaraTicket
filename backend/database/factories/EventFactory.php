<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;
        $totalTickets = fake()->numberBetween(100, 500);
        $startDatetime = fake()->dateTimeBetween('+1 day', '+30 days');
        $endDatetime = (clone $startDatetime)->modify('+' . fake()->numberBetween(1, 8) . ' hours');

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'slug' => Str::slug(fake()->unique()->sentence(4)) . '-' . Str::random(6),
            'description' => fake()->paragraphs(2, true),
            'price' => round(fake()->randomFloat(2, 10, 500), 2),
            'total_tickets' => $totalTickets,
            'available_tickets' => $totalTickets,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'location' => fake()->city() . ', ' . fake()->country(),
            'image_url' => function () use (&$counter) {
                $number = str_pad($counter, 2, '0', STR_PAD_LEFT);
                $counter = $counter >= 8 ? 1 : $counter + 1;
                return "https://res.cloudinary.com/dbelkcsrq/image/upload/lara-ticket/covers/{$number}.jpg";
            },
        ];
    }

    public function past(): static
    {
        return $this->state(fn() => [
            'start_datetime' => fake()->dateTimeBetween('-30 days', '-2 days'),
            'end_datetime' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function soldOut(): static
    {
        return $this->state(fn() => [
            'available_tickets' => 0,
        ]);
    }
}
