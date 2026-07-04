<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbstractOfQuotation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class AbstractOfQuotationService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return AbstractOfQuotation::with(['preparedBy', 'rfq'])
            ->when($filters['rfq_id'] ?? null, fn ($q, $v) => $q->where('rfq_id', $v))
            ->when($filters['prepared_by_id'] ?? null, fn ($q, $v) => $q->where('prepared_by_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('recommended_supplier', 'like', "%{$v}%"))
            ->latest()
            ->paginate(15);
    }

    public function show(AbstractOfQuotation $abstract): AbstractOfQuotation
    {
        return $abstract->load(['preparedBy', 'rfq']);
    }

    public function store(array $validated): AbstractOfQuotation
    {
        return DB::transaction(fn (): AbstractOfQuotation => AbstractOfQuotation::create($validated));
    }

    public function update(AbstractOfQuotation $abstract, array $validated): AbstractOfQuotation
    {
        return DB::transaction(function () use ($abstract, $validated): AbstractOfQuotation {
            $abstract->update($validated);

            return $abstract->refresh()->load(['preparedBy']);
        });
    }

    public function destroy(AbstractOfQuotation $abstract): void
    {
        DB::transaction(function () use ($abstract): void {
            $abstract->delete();
        });
    }
}
