<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class UserService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return User::with(['role', 'office'])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($q) => $q->where('first_name', 'like', "%{$v}%")
                    ->orWhere('last_name', 'like', "%{$v}%")
                    ->orWhere('email', 'like', "%{$v}%")
            ))
            ->when($filters['role_id'] ?? null, fn ($q, $v) => $q->where('role_id', $v))
            ->when($filters['office_id'] ?? null, fn ($q, $v) => $q->where('office_id', $v))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);
    }

    public function show(User $user): User
    {
        return $user->load(['role', 'office']);
    }

    public function store(array $validated): User
    {
        return DB::transaction(fn (): User => User::create($validated));
    }

    public function update(User $user, array $validated): User
    {
        return DB::transaction(function () use ($user, $validated): User {
            $user->update($validated);

            return $user->refresh()->load(['role', 'office']);
        });
    }

    public function destroy(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->delete();
        });
    }
}
