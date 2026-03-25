<?php

use App\Models\Event;
use App\Models\User;

test('anyone can list events', function () {
    Event::factory()->count(3)->create();

    $response = $this->getJson('/api/events');

    $response->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'last_page', 'per_page', 'total']);

    expect($response->json('data'))->toHaveCount(3);
});

test('events listing is paginated', function () {
    Event::factory()->count(20)->create();

    $response = $this->getJson('/api/events?per_page=5');

    expect($response->json('data'))->toHaveCount(5);
    expect($response->json('last_page'))->toBeGreaterThan(1);
});

test('per_page is capped at 50', function () {
    Event::factory()->count(3)->create();

    $response = $this->getJson('/api/events?per_page=100');

    expect($response->json('per_page'))->toBeLessThanOrEqual(50);
});

test('events can be searched by title', function () {
    Event::factory()->create(['title' => 'Laravel Conference 2026']);
    Event::factory()->create(['title' => 'Vue Meetup']);

    $response = $this->getJson('/api/events?search=Laravel');

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.title'))->toContain('Laravel');
});

test('events can be filtered by upcoming', function () {
    Event::factory()->create(['start_datetime' => now()->addDays(5)]);
    Event::factory()->past()->create();

    $response = $this->getJson('/api/events?upcoming=true');

    expect($response->json('data'))->toHaveCount(1);
});

test('events can be filtered by location', function () {
    Event::factory()->create(['location' => 'Cairo, Egypt']);
    Event::factory()->create(['location' => 'London, UK']);

    $response = $this->getJson('/api/events?location=Cairo');

    expect($response->json('data'))->toHaveCount(1);
});

test('anyone can view a single event', function () {
    $event = Event::factory()->create();

    $response = $this->getJson("/api/events/{$event->id}");

    $response->assertOk()
        ->assertJsonPath('event.id', $event->id);
});

test('admin can create an event', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->postJson('/api/events', [
        'title' => 'New Event',
        'description' => 'A great event.',
        'price' => 150.00,
        'total_tickets' => 200,
        'start_datetime' => now()->addDays(10)->toDateTimeString(),
        'end_datetime' => now()->addDays(11)->toDateTimeString(),
        'location' => 'Cairo, Egypt',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('event.title', 'New Event');

    $this->assertDatabaseHas('events', ['title' => 'New Event']);
});

test('created event auto-generates slug and sets available_tickets', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->postJson('/api/events', [
        'title' => 'Slug Test Event',
        'description' => 'Testing slug generation.',
        'price' => 50,
        'total_tickets' => 100,
        'start_datetime' => now()->addDays(5)->toDateTimeString(),
        'location' => 'Test City',
    ]);

    $event = Event::latest()->first();
    expect($event->slug)->toContain('slug-test-event');
    expect($event->available_tickets)->toBe(100);
});

test('non-admin cannot create event', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/events', [
        'title' => 'Forbidden Event',
        'description' => 'Should not be created.',
        'price' => 100,
        'total_tickets' => 50,
        'start_datetime' => now()->addDays(5)->toDateTimeString(),
        'location' => 'Nowhere',
    ]);

    $response->assertStatus(403);
});

test('unauthenticated user cannot create event', function () {
    $response = $this->postJson('/api/events', [
        'title' => 'No Auth Event',
        'description' => 'Should fail.',
        'price' => 100,
        'total_tickets' => 50,
        'start_datetime' => now()->addDays(5)->toDateTimeString(),
        'location' => 'Nowhere',
    ]);

    $response->assertStatus(401);
});

test('event creation validates required fields', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->postJson('/api/events', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'price', 'total_tickets', 'start_datetime', 'location']);
});

test('admin can update an event', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create(['user_id' => $admin->id]);

    $response = $this->actingAs($admin)->putJson("/api/events/{$event->id}", [
        'title' => 'Updated Title',
        'description' => $event->description,
        'price' => $event->price,
        'total_tickets' => $event->total_tickets,
        'start_datetime' => $event->start_datetime->toDateTimeString(),
        'location' => $event->location,
    ]);

    $response->assertOk()
        ->assertJsonPath('event.title', 'Updated Title');
});

test('admin can delete an event', function () {
    $admin = User::factory()->admin()->create();
    $event = Event::factory()->create(['user_id' => $admin->id]);

    $response = $this->actingAs($admin)->deleteJson("/api/events/{$event->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('non-owner non-admin cannot delete event', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    $response = $this->actingAs($user)->deleteJson("/api/events/{$event->id}");

    $response->assertStatus(403);
});
