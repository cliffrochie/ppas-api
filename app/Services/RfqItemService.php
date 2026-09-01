<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RfqItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class RfqItemService
{
    private const ALLOWED_SORTS = [
        'id', 'rfq_id', 'pr_item_id', 'item_description', 'quantity', 'unit_of_measure', 'created_at', 'updated_at',
    ];

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return RfqItem::query()
            ->when($filters['rfq_id'] ?? null, fn ($q, $v) => $q->where('rfq_id', $v))
            ->when($filters['pr_item_id'] ?? null, fn ($q, $v) => $q->where('pr_item_id', $v))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->latest()
            )
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
