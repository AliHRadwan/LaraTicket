<?php

use App\Models\User;

test('verified user can login', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'Password1',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'Password1',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'user', 'token']);
});

test('login returns sanctum token', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'Password1',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'Password1',
    ]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('login fails with wrong password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'Password1',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'WrongPassword1',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('unverified user cannot login', function () {
    User::factory()->unverified()->create([
        'email' => 'unverified@example.com',
        'password' => 'Password1',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'unverified@example.com',
        'password' => 'Password1',
    ]);

    $response->assertStatus(403)
        ->assertJson(['requires_verification' => true]);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->postJson('/api/auth/logout');

    $response->assertOk()
        ->assertJson(['message' => 'Logged out successfully.']);

    expect($user->tokens()->count())->toBe(0);
});

test('authenticated user can get their profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/auth/user');

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email);
});

test('unauthenticated user cannot access profile', function () {
    $response = $this->getJson('/api/auth/user');

    $response->assertStatus(401);
});

test('unauthenticated user cannot logout', function () {
    $response = $this->postJson('/api/auth/logout');

    $response->assertStatus(401);
});
