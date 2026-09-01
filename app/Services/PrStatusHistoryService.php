<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PrStatusHistory;
use Illuminate\Pagination\LengthAwarePaginator;

final class PrStatusHistoryService
{
    private const ALLOWED_SORTS = [
        'id', 'purchase_request_id', 'actor_id', 'from_status', 'to_status', 'alobs_number', 'acted_at', 'created_at', 'updated_at',
    ];

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return PrStatusHistory::with(['actor', 'purchaseRequest'])
            ->when($filters['purchase_request_id'] ?? null, fn ($q, $v) => $q->where('purchase_request_id', $v))
            ->when($filters['actor_id'] ?? null, fn ($q, $v) => $q->where('actor_id', $v))
            ->when($filters['from_status'] ?? null, fn ($q, $v) => $q->where('from_status', $v))
            ->when($filters['to_status'] ?? null, fn ($q, $v) => $q->where('to_status', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->search($v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('acted_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('acted_at', '<=', $v))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->latest()
            )
            ->paginate(15);
    }

    public function show(PrStatusHistory $history): PrStatusHistory
    {
        return $history->load(['actor', 'purchaseRequest']);
    }

    /**
     * Append an immutable row to the status history log.
     * Called by PurchaseRequestService on every status transition.
     *
     * @param  string|null  $fromStatus  null only on the very first transition from creation
     * @param  string|null  $alobsNumber  captured here when Budget Officer encodes it (C3 fix)
     */
    public function record(
        int $purchaseRequestId,
        int $actorId,
        ?string $fromStatus,
        string $toStatus,
        ?string $remarks = null,
        ?string $alobsNumber = null,
    ): PrStatusHistory {
        return PrStatusHistory::create([
            'purchase_request_id' => $purchaseRequestId,
            'actor_id' => $actorId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'remarks' => $remarks,
            'alobs_number' => $alobsNumber,
            'acted_at' => now(),
        ]);
    }
}
