<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RfqItem;
use App\Models\User;

final class RfqItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RfqItem $item): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, RfqItem $item): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, RfqItem $item): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
