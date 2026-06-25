<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BacResolution;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class BacResolutionService
{
    public function list(): LengthAwarePaginator
    {
        return BacResolution::with(['preparedBy', 'abstractOfQuotation'])
            ->latest()
            ->paginate(15);
    }

    public function show(BacResolution $resolution): BacResolution
    {
        return $resolution->load(['preparedBy', 'abstractOfQuotation']);
    }

    public function store(array $validated): BacResolution
    {
        return DB::transaction(fn (): BacResolution => BacResolution::create($validated));
    }

    public function update(BacResolution $resolution, array $validated): BacResolution
    {
        return DB::transaction(function () use ($resolution, $validated): BacResolution {
            $resolution->update($validated);

            return $resolution->refresh()->load(['preparedBy']);
        });
    }

    public function destroy(BacResolution $resolution): void
    {
        DB::transaction(function () use ($resolution): void { $resolution->delete(); });
    }
}
