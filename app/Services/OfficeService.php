<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Office;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class OfficeService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Office::query()
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%")
            ))
            ->orderBy('name')
            ->paginate(15);
    }

    public function store(array $validated): Office
    {
        return DB::transaction(fn (): Office => Office::create($validated));
    }

    public function update(Office $office, array $validated): Office
    {
        return DB::transaction(function () use ($office, $validated): Office {
            $office->update($validated);

            return $office->refresh();
        });
    }

    public function destroy(Office $office): void
    {
        DB::transaction(function () use ($office): void {
            $office->delete();
        });
    }
}
