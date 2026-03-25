<?php

namespace App\Http\Controllers\Auth;

use App\DTOs\NotificationDTO;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\VerifyEmail;
use App\Models\User;
use App\Notifications\NotificationSystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $this->sendVerificationEmail($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('User registered', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json([
            'message' => 'Registration successful. Please check your email to verify your account.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $user = User::where('email', $request->email)->first();

        if (! $user->hasVerifiedEmail()) {
            Log::notice('Login blocked: email not verified', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'message' => 'Please verify your email address before logging in.',
                'requires_verification' => true,
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        Log::info('User logged in', ['user_id' => $user->id]);

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $request->user()->currentAccessToken()->delete();

        Log::info('User logged out', ['user_id' => $userId]);

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            Log::warning('Invalid email verification attempt', [
                'user_id' => $id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Invalid verification link.',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        $user->markEmailAsVerified();

        Log::info('Email verified', ['user_id' => $user->id]);

        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        $this->sendVerificationEmail($user);

        Log::info('Verification email resent', ['user_id' => $user->id]);

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }

    private function sendVerificationEmail(User $user): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $user->notify(new NotificationSystem(new NotificationDTO(
            type: NotificationType::VERIFY_EMAIL,
            title: 'Verify Your Email Address',
            body: 'Please verify your email address to start using your account.',
            channels: ['mail'],
            mailable: new VerifyEmail($user, $verificationUrl),
            actionUrl: $verificationUrl,
            actionText: 'Verify Email Address',
        )));
    }
}
