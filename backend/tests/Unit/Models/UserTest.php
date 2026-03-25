<?php

use App\Models\Order;
use App\Models\User;

test('user has correct fillable attributes', function () {
    $user = new User();

    expect($user->getFillable())->toBe(['name', 'email', 'password', 'is_admin']);
});

test('user has correct hidden attributes', function () {
    $user = new User();

    expect($user->getHidden())->toBe(['password', 'remember_token']);
});

test('user password is hashed', function () {
    $user = User::factory()->create(['password' => 'secret123']);

    expect($user->password)->not->toBe('secret123');
    expect(password_verify('secret123', $user->password))->toBeTrue();
});

test('user has orders relationship', function () {
    $user = User::factory()->create();

    expect($user->orders())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('user orders relationship returns correct records', function () {
    $user = User::factory()->create();
    Order::factory()->count(3)->create(['user_id' => $user->id]);
    Order::factory()->create(); // different user

    expect($user->orders)->toHaveCount(3);
});

test('admin user can access filament panel', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->canAccessPanel(new \Filament\Panel()))->toBeTrue();
});

test('regular user cannot access filament panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(new \Filament\Panel()))->toBeFalse();
});

test('is_admin is cast to boolean', function () {
    $user = User::factory()->admin()->create();

    expect($user->is_admin)->toBeBool();
    expect($user->is_admin)->toBeTrue();
});

test('email_verified_at is cast to datetime', function () {
    $user = User::factory()->create();

    expect($user->email_verified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('unverified user has null email_verified_at', function () {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
    expect($user->hasVerifiedEmail())->toBeFalse();
});
