<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseRequestItem;
use App\Models\User;

final class PurchaseRequestItemPolicy
{
    /**
     * Items belong to purchase requests.
     * procurement_officer has full access.
     * requester can manage items on their own draft purchase requests.
     * budget_officer is read-only.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseRequestItem $item): bool
    {
        if (in_array($user->role?->name, ['procurement_officer', 'budget_officer'], true)) {
            return true;
        }

        // requester may view items on their own purchase requests
        return $item->purchaseRequest?->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['requester', 'procurement_officer'], true);
    }

    public function update(User $user, PurchaseRequestItem $item): bool
    {
        if ($user->role?->name === 'procurement_officer') {
            return true;
        }

        return $item->purchaseRequest?->requester_id === $user->id
            && $item->purchaseRequest?->status === 'draft';
    }

    public function delete(User $user, PurchaseRequestItem $item): bool
    {
        if ($user->role?->name === 'procurement_officer') {
            return true;
        }

        return $item->purchaseRequest?->requester_id === $user->id
            && $item->purchaseRequest?->status === 'draft';
    }
}
