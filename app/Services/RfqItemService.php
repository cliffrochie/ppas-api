<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RfqItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class RfqItemService
{
    public function list(): LengthAwarePaginator
    {
        return RfqItem::latest()->paginate(15);
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
        DB::transaction(function () use ($item): void { $item->delete(); });
    }
}
