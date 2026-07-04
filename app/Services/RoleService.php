<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class RoleService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Role::query()
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')
            ->paginate(15);
    }

    public function store(array $validated): Role
    {
        return DB::transaction(fn (): Role => Role::create($validated));
    }

    public function update(Role $role, array $validated): Role
    {
        return DB::transaction(function () use ($role, $validated): Role {
            $role->update($validated);

            return $role->refresh();
        });
    }

    public function destroy(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            $role->delete();
        });
    }
}
