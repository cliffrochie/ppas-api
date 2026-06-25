<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    /**
     * All authenticated users may view user listings (needed for selectors).
     * Only procurement_officer may create, update, or delete users.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, User $model): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
