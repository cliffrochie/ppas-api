<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LoginLog;
use App\Models\User;

final class LoginLogPolicy
{
    /**
     * Login logs are read-only.
     * Only procurement_officer may view them.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function view(User $user, LoginLog $loginLog): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
