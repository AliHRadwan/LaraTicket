<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/email/resend', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:verification');
    });
});

/*
|--------------------------------------------------------------------------
| Events (public read, admin write)
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:api')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'verified', 'throttle:api'])->group(function () {
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Orders (authenticated + verified)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'verified', 'throttle:api'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store'])
        ->middleware('throttle:orders');
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Payments (admin only — enforced by policy)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'verified', 'throttle:api'])->group(function () {
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Stripe Webhook (no auth — verified by Stripe signature)
|--------------------------------------------------------------------------
*/

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
