<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CanvassResponse;
use App\Models\User;

final class CanvassResponsePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CanvassResponse $canvassResponse): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, CanvassResponse $canvassResponse): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, CanvassResponse $canvassResponse): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
