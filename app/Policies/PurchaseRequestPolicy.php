<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

final class PurchaseRequestPolicy
{
    /**
     * Permission matrix:
     *   requester          — viewAny (own only enforced at service level), view (own), create, update (own draft), delete (own draft)
     *   procurement_officer — full access
     *   budget_officer      — viewAny + view (read-only)
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->role?->name === 'procurement_officer') {
            return true;
        }

        if ($user->role?->name === 'budget_officer') {
            return true;
        }

        // requester may only view their own purchase requests
        return $purchaseRequest->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['requester', 'procurement_officer'], true);
    }

    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->role?->name === 'procurement_officer') {
            return true;
        }

        // budget_officer may update (e.g. to encode alobs_number)
        if ($user->role?->name === 'budget_officer') {
            return true;
        }

        // requester may update their own PRs when in draft or returned status
        // (draft → submitted, returned → submitted are both requester-triggered transitions)
        return $purchaseRequest->requester_id === $user->id
            && in_array($purchaseRequest->status, ['draft', 'returned'], true);
    }

    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        if ($user->role?->name === 'procurement_officer') {
            return true;
        }

        // requester may only delete their own draft purchase requests
        return $purchaseRequest->requester_id === $user->id && $purchaseRequest->status === 'draft';
    }
}
