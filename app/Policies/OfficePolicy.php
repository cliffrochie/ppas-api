<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Office;
use App\Models\User;

final class OfficePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Office $office): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, Office $office): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, Office $office): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
