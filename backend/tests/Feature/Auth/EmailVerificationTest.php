<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('user can verify email with valid signed link', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $response = $this->getJson($verificationUrl);

    $response->assertOk()
        ->assertJson(['message' => 'Email verified successfully.']);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email verification fails with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'invalid-hash']
    );

    $response = $this->getJson($url);

    $response->assertStatus(403);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('already verified user gets appropriate message', function () {
    $user = User::factory()->create(); // verified by default

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
    );

    $response = $this->getJson($url);

    $response->assertOk()
        ->assertJson(['message' => 'Email already verified.']);
});

test('authenticated user can resend verification email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->postJson('/api/auth/email/resend');

    $response->assertOk()
        ->assertJson(['message' => 'Verification email sent.']);

    Notification::assertSentTo($user, \App\Notifications\NotificationSystem::class);
});

test('already verified user gets message on resend attempt', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/auth/email/resend');

    $response->assertOk()
        ->assertJson(['message' => 'Email already verified.']);
});
