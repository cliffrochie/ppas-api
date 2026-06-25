<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseOrderItem;
use App\Models\User;

final class PurchaseOrderItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseOrderItem $item): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, PurchaseOrderItem $item): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, PurchaseOrderItem $item): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
