<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Office;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class OfficeService
{
    private const ALLOWED_SORTS = [
        'id', 'name', 'code', 'created_at', 'updated_at',
    ];

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return Office::query()
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->search($v))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->orderBy('name')
            )
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
