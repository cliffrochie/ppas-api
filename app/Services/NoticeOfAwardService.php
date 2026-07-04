<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NoticeOfAward;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class NoticeOfAwardService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return NoticeOfAward::with(['bacResolution'])
            ->when($filters['bac_resolution_id'] ?? null, fn ($q, $v) => $q->where('bac_resolution_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($q) => $q->where('awarded_supplier', 'like', "%{$v}%")
                    ->orWhere('noa_number', 'like', "%{$v}%")
            ))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '<=', $v))
            ->latest()
            ->paginate(15);
    }

    public function show(NoticeOfAward $noa): NoticeOfAward
    {
        return $noa->load(['bacResolution']);
    }

    public function store(array $validated): NoticeOfAward
    {
        return DB::transaction(fn (): NoticeOfAward => NoticeOfAward::create($validated));
    }

    public function update(NoticeOfAward $noa, array $validated): NoticeOfAward
    {
        return DB::transaction(function () use ($noa, $validated): NoticeOfAward {
            $noa->update($validated);

            return $noa->refresh();
        });
    }

    public function destroy(NoticeOfAward $noa): void
    {
        DB::transaction(function () use ($noa): void {
            $noa->delete();
        });
    }
}
