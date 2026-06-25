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
}
