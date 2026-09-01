<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrderItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PurchaseOrderItemService
{
    private const ALLOWED_SORTS = [
        'id', 'purchase_order_id', 'pr_item_id', 'item_description', 'quantity', 'unit_of_measure', 'unit_cost', 'total_cost', 'created_at', 'updated_at',
    ];

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return PurchaseOrderItem::query()
            ->when($filters['purchase_order_id'] ?? null, fn ($q, $v) => $q->where('purchase_order_id', $v))
            ->when($filters['pr_item_id'] ?? null, fn ($q, $v) => $q->where('pr_item_id', $v))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->latest()
            )
            ->paginate(15);
    }

    public function store(array $validated): PurchaseOrderItem
    {
        return DB::transaction(fn (): PurchaseOrderItem => PurchaseOrderItem::create($validated));
    }

    public function update(PurchaseOrderItem $item, array $validated): PurchaseOrderItem
    {
        return DB::transaction(function () use ($item, $validated): PurchaseOrderItem {
            $item->update($validated);

            return $item->refresh();
        });
    }

    public function destroy(PurchaseOrderItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $item->delete();
        });
    }
}
