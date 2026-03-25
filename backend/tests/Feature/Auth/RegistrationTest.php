<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('user can register with valid data', function () {
    Notification::fake();

    $response = $this->postJson('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'user', 'token']);

    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
});

test('registration returns a sanctum token', function () {
    Notification::fake();

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('registration fails with missing name', function () {
    $response = $this->postJson('/api/auth/register', [
        'email' => 'test@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

test('registration fails with invalid email', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test',
        'email' => 'not-an-email',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('registration fails with weak password', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => '12345678',
        'password_confirmation' => '12345678',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

test('registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test',
        'email' => 'taken@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('registration fails with mismatched password confirmation', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Different1',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

test('verification notification is dispatched on registration', function () {
    Notification::fake();

    $this->postJson('/api/auth/register', [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    Notification::assertSentTo($user, \App\Notifications\NotificationSystem::class);
});
