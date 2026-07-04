<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RfqItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class RfqItemService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return RfqItem::query()
            ->when($filters['rfq_id'] ?? null, fn ($q, $v) => $q->where('rfq_id', $v))
            ->when($filters['pr_item_id'] ?? null, fn ($q, $v) => $q->where('pr_item_id', $v))
            ->latest()
            ->paginate(15);
    }

    public function store(array $validated): RfqItem
    {
        return DB::transaction(fn (): RfqItem => RfqItem::create($validated));
    }

    public function update(RfqItem $item, array $validated): RfqItem
    {
        return DB::transaction(function () use ($item, $validated): RfqItem {
            $item->update($validated);

            return $item->refresh();
        });
    }

    public function destroy(RfqItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $item->delete();
        });
    }
}
