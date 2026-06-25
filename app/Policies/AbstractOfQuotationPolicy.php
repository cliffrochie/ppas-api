<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AbstractOfQuotation;
use App\Models\User;

final class AbstractOfQuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AbstractOfQuotation $abstractOfQuotation): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, AbstractOfQuotation $abstractOfQuotation): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, AbstractOfQuotation $abstractOfQuotation): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
