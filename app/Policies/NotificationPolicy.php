<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

final class NotificationPolicy
{
    /**
     * Users may only view and mark-read their own notifications.
     * procurement_officer has full visibility for admin purposes.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Notification $notification): bool
    {
        if ($user->role?->name === 'procurement_officer') {
            return true;
        }

        return $notification->user_id === $user->id;
    }

    public function update(User $user, Notification $notification): bool
    {
        if ($user->role?->name === 'procurement_officer') {
            return true;
        }

        return $notification->user_id === $user->id;
    }
}
