<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NoticeOfAward;
use App\Models\User;

final class NoticeOfAwardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, NoticeOfAward $noticeOfAward): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function update(User $user, NoticeOfAward $noticeOfAward): bool
    {
        return $user->role?->name === 'procurement_officer';
    }

    public function delete(User $user, NoticeOfAward $noticeOfAward): bool
    {
        return $user->role?->name === 'procurement_officer';
    }
}
