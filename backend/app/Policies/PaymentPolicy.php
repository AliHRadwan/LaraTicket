<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin ?? false;
    }

    public function view(User $user, Payment $payment): bool
    {
        return ($user->is_admin ?? false) || $user->id === $payment->order->user_id;
    }

    public function create(User $user): bool
    {
        return $user->is_admin ?? false;
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->is_admin ?? false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->is_admin ?? false;
    }
}
