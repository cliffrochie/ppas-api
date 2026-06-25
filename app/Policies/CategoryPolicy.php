<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

final class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, Category $category): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
