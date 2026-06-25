<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BacResolution;
use App\Models\User;

final class BacResolutionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BacResolution $bacResolution): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, BacResolution $bacResolution): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, BacResolution $bacResolution): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
