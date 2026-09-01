<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Pagination\LengthAwarePaginator;

final class AuditLogService
{
    private const ALLOWED_SORTS = [
        'id', 'user_id', 'auditable_type', 'auditable_id', 'event', 'field', 'ip_address', 'created_at',
    ];

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return AuditLog::with(['user'])
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['auditable_type'] ?? null, fn ($q, $v) => $q->where('auditable_type', $v))
            ->when($filters['auditable_id'] ?? null, fn ($q, $v) => $q->where('auditable_id', $v))
            ->when($filters['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($filters['field'] ?? null, fn ($q, $v) => $q->where('field', $v))
            ->when($filters['ip_address'] ?? null, fn ($q, $v) => $q->where('ip_address', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->latest()
            )
            ->paginate(15);
    }

    public function show(AuditLog $auditLog): AuditLog
    {
        return $auditLog->load(['user']);
    }
}
