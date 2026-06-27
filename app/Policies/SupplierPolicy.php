<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

final class SupplierPolicy
{
    /**
     * Suppliers are managed by procurement_officer.
     * budget_officer and requester have read-only access.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
