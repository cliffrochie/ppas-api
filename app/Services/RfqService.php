<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rfq;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class RfqService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Rfq::with(['preparedBy', 'purchaseRequest'])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('rfq_number', 'like', "%{$v}%"))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['prepared_by_id'] ?? null, fn ($q, $v) => $q->where('prepared_by_id', $v))
            ->when($filters['purchase_request_id'] ?? null, fn ($q, $v) => $q->where('purchase_request_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('deadline', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('deadline', '<=', $v))
            ->latest()
            ->paginate(15);
    }

    public function show(Rfq $rfq): Rfq
    {
        return $rfq->load(['preparedBy', 'purchaseRequest', 'items']);
    }

    public function store(array $validated): Rfq
    {
        return DB::transaction(function () use ($validated): Rfq {
            // rfq_number is auto-generated — never comes from user input
            $validated['rfq_number'] = $this->generateRfqNumber();

            return Rfq::create($validated);
        });
    }

    public function update(Rfq $rfq, array $validated): Rfq
    {
        return DB::transaction(function () use ($rfq, $validated): Rfq {
            $rfq->update($validated);

            return $rfq->refresh()->load(['preparedBy']);
        });
    }

    public function destroy(Rfq $rfq): void
    {
        DB::transaction(function () use ($rfq): void {
            $rfq->delete();
        });
    }

    private function generateRfqNumber(): string
    {
        // Must be called inside a DB::transaction — lockForUpdate() is a no-op
        // outside a transaction and would produce duplicates under concurrency.
        $year = now()->year;

        $last = Rfq::whereYear('created_at', $year)
            ->whereNotNull('rfq_number')
            ->lockForUpdate()
            ->max('rfq_number');

        $next = $last !== null ? ((int) substr($last, -5)) + 1 : 1;

        return sprintf('RFQ-%d-%05d', $year, $next);
    }
}
