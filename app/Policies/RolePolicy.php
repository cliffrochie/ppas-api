<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

final class RolePolicy
{
    /**
     * Only procurement_officer may manage configuration data (roles, offices, categories).
     * All authenticated users may read.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Role $role): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, Role $role): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
