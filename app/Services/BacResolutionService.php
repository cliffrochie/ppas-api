<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BacResolution;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class BacResolutionService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return BacResolution::with(['preparedBy', 'abstractOfQuotation'])
            ->when($filters['abstract_of_quotation_id'] ?? null, fn ($q, $v) => $q->where('abstract_of_quotation_id', $v))
            ->when($filters['prepared_by_id'] ?? null, fn ($q, $v) => $q->where('prepared_by_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('resolution_number', 'like', "%{$v}%"))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '<=', $v))
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
        DB::transaction(function () use ($resolution): void {
            $resolution->delete();
        });
    }
}
