<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PrAttachment;
use App\Models\User;

final class PrAttachmentPolicy
{
    /**
     * Attachments belong to purchase requests.
     * procurement_officer has full access.
     * requester can manage attachments on their own purchase requests.
     * budget_officer is read-only.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PrAttachment $attachment): bool
    {
        if (in_array($user->role?->name, ['procurement_officer', 'budget_officer', 'bac_secretariat'], true)) {
            return true;
        }

        return $attachment->purchaseRequest?->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['requester', 'procurement_officer'], true);
    }

    public function update(User $user, PrAttachment $attachment): bool
    {
        if ($user->role?->name === 'procurement_officer') {
            return true;
        }

        return $attachment->purchaseRequest?->requester_id === $user->id;
    }

    public function delete(User $user, PrAttachment $attachment): bool
    {
        if ($user->role?->name === 'procurement_officer') {
            return true;
        }

        return $attachment->purchaseRequest?->requester_id === $user->id;
    }
}
