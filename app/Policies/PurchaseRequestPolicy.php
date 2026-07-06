<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

final class PurchaseRequestPolicy
{
    /**
     * Permission matrix:
     *   requester           — viewAny (own only enforced at service level), view (own), create, update (own draft), delete (own draft)
     *   procurement_officer — full access
     *   bac_secretariat     — viewAny + view (read-only); write eligibility gated by PurchaseRequestTransitions
     *   budget_officer      — viewAny + view (read-only); write eligibility gated by PurchaseRequestTransitions
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

        if (in_array($user->role?->name, ['budget_officer', 'bac_secretariat'], true)) {
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

        // budget_officer / bac_secretariat may update (base eligibility; the actual
        // status transition is further gated by PurchaseRequestTransitions)
        if (in_array($user->role?->name, ['budget_officer', 'bac_secretariat'], true)) {
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
