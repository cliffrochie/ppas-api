<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

final class AuditLogPolicy
{
    /**
     * Audit logs are read-only.
     * Only procurement_officer and budget_officer may view them; requester is denied.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['procurement_officer', 'budget_officer'], true);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return in_array($user->role?->name, ['procurement_officer', 'budget_officer'], true);
    }
}
