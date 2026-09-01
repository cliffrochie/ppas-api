<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class RoleService
{
    private const ALLOWED_SORTS = [
        'id', 'name', 'description', 'created_at', 'updated_at',
    ];

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return Role::query()
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->orderBy('name')
            )
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
