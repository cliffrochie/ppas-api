<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseRequestItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PurchaseRequestItemService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return PurchaseRequestItem::query()
            ->when($filters['purchase_request_id'] ?? null, fn ($q, $v) => $q->where('purchase_request_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($q) => $q->where('item_description', 'like', "%{$v}%")
                    ->orWhere('unit_of_measure', 'like', "%{$v}%")
            ))
            ->latest()
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
