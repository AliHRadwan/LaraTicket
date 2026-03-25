<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Event $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin ?? false;
    }

    public function update(User $user, Event $event): bool
    {
        return ($user->is_admin ?? false) || $user->id === $event->user_id;
    }

    public function delete(User $user, Event $event): bool
    {
        return ($user->is_admin ?? false) || $user->id === $event->user_id;
    }
}
