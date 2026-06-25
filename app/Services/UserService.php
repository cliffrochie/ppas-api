<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class UserService
{
    public function list(): LengthAwarePaginator
    {
        return User::with(['role', 'office'])
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
        DB::transaction(function () use ($user): void { $user->delete(); });
    }
}
