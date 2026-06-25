<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PrStatusHistory;
use App\Models\User;

final class PrStatusHistoryPolicy
{
    /**
     * Status history is append-only and read-only for all roles.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PrStatusHistory $prStatusHistory): bool
    {
        return true;
    }
}
