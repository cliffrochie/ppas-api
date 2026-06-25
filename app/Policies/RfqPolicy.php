<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rfq;
use App\Models\User;

final class RfqPolicy
{
    /**
     * procurement_officer manages RFQs.
     * All other roles may view.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rfq $rfq): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, Rfq $rfq): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, Rfq $rfq): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
