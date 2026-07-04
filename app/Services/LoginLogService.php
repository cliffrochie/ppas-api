<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LoginLog;
use Illuminate\Pagination\LengthAwarePaginator;

final class LoginLogService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return LoginLog::with(['user'])
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($q) => $q->where('email', 'like', "%{$v}%")
                    ->orWhere('ip_address', 'like', "%{$v}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate(15);
    }

    public function show(LoginLog $loginLog): LoginLog
    {
        return $loginLog->load(['user']);
    }
}
