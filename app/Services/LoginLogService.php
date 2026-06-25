<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LoginLog;
use Illuminate\Pagination\LengthAwarePaginator;

final class LoginLogService
{
    public function list(): LengthAwarePaginator
    {
        return LoginLog::with(['user'])
            ->latest()
            ->paginate(15);
    }

    public function show(LoginLog $loginLog): LoginLog
    {
        return $loginLog->load(['user']);
    }
}
