<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rfq;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class RfqService
{
    public function list(): LengthAwarePaginator
    {
        return Rfq::with(['preparedBy', 'purchaseRequest'])
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
        DB::transaction(function () use ($rfq): void { $rfq->delete(); });
    }

    private function generateRfqNumber(): string
    {
        $year  = now()->format('Y');
        $count = Rfq::whereYear('created_at', $year)->count() + 1;

        return sprintf('RFQ-%s-%04d', $year, $count);
    }
}
