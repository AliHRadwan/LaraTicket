<?php

use App\Models\Event;
use App\Models\Order;
use App\Models\User;

test('event has correct fillable attributes', function () {
    $event = new Event();

    expect($event->getFillable())->toBe([
        'user_id', 'title', 'slug', 'description', 'price',
        'total_tickets', 'available_tickets', 'start_datetime',
        'end_datetime', 'location', 'image_url',
    ]);
});

test('event price is cast to decimal', function () {
    $event = Event::factory()->create(['price' => 99.99]);

    expect($event->price)->toBe('99.99');
});

test('event dates are cast to datetime', function () {
    $event = Event::factory()->create();

    expect($event->start_datetime)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('event belongs to a user', function () {
    $event = Event::factory()->create();

    expect($event->user())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($event->user)->toBeInstanceOf(User::class);
});

test('event has many orders', function () {
    $event = Event::factory()->create();
    Order::factory()->count(2)->create(['event_id' => $event->id]);

    expect($event->orders())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    expect($event->orders)->toHaveCount(2);
});

test('event factory sold out state sets zero available tickets', function () {
    $event = Event::factory()->soldOut()->create();

    expect($event->available_tickets)->toBe(0);
});

test('event factory past state sets past dates', function () {
    $event = Event::factory()->past()->create();

    expect($event->start_datetime->isPast())->toBeTrue();
});
