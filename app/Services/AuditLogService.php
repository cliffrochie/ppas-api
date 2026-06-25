<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;

final class AuditLogService
{
    public function list(): LengthAwarePaginator
    {
        return AuditLog::with(['user'])
            ->latest()
            ->paginate(15);
    }

    public function show(AuditLog $auditLog): AuditLog
    {
        return $auditLog->load(['user']);
    }
}
