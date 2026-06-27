<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PrStatusHistory;
use Illuminate\Pagination\LengthAwarePaginator;

final class PrStatusHistoryService
{
    public function list(): LengthAwarePaginator
    {
        return PrStatusHistory::with(['actor', 'purchaseRequest'])
            ->latest()
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
     * @param string|null $fromStatus  null only on the very first transition from creation
     * @param string|null $alobsNumber  captured here when Budget Officer encodes it (C3 fix)
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
            'actor_id'            => $actorId,
            'from_status'         => $fromStatus,
            'to_status'           => $toStatus,
            'remarks'             => $remarks,
            'alobs_number'        => $alobsNumber,
            'acted_at'            => now(),
        ]);
    }
}
