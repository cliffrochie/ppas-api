<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseRequestItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PurchaseRequestItemService
{
    public function list(): LengthAwarePaginator
    {
        return PurchaseRequestItem::latest()->paginate(15);
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
        DB::transaction(function () use ($item): void { $item->delete(); });
    }
}
