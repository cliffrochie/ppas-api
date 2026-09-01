<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseRequestItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PurchaseRequestItemService
{
    private const ALLOWED_SORTS = [
        'id', 'purchase_request_id', 'item_description', 'quantity', 'unit_of_measure', 'unit_cost', 'total_cost', 'created_at', 'updated_at',
    ];

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return PurchaseRequestItem::query()
            ->when($filters['purchase_request_id'] ?? null, fn ($q, $v) => $q->where('purchase_request_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->search($v))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->latest()
            )
            ->paginate(15);
    }

    public function store(array $validated): PurchaseRequestItem
    {
        return DB::transaction(fn (): PurchaseRequestItem => PurchaseRequestItem::create($validated));
    }

    public function update(PurchaseRequestItem $item, array $validated): PurchaseRequestItem
    {
        return DB::transaction(function () use ($item, $validated): PurchaseRequestItem {
            $item->update($validated);

            return $item->refresh();
        });
    }

    public function destroy(PurchaseRequestItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $item->delete();
        });
    }
}
