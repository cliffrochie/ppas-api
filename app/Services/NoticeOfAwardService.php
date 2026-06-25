<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NoticeOfAward;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class NoticeOfAwardService
{
    public function list(): LengthAwarePaginator
    {
        return NoticeOfAward::with(['bacResolution'])
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
        DB::transaction(function () use ($noa): void { $noa->delete(); });
    }
}
