<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SupplierDocument;
use App\Models\User;

final class SupplierDocumentPolicy
{
    /**
     * Supplier documents are managed by procurement_officer.
     * All authenticated users can view/download documents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupplierDocument $document): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, SupplierDocument $document): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
