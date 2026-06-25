<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PurchaseOrderService
{
    public function list(): LengthAwarePaginator
    {
        return PurchaseOrder::with(['preparedBy', 'purchaseRequest'])
            ->latest()
            ->paginate(15);
    }

    public function show(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        return $purchaseOrder->load(['preparedBy', 'purchaseRequest', 'items']);
    }

    public function store(array $validated): PurchaseOrder
    {
        return DB::transaction(function () use ($validated): PurchaseOrder {
            // po_number is auto-generated — never comes from user input
            $validated['po_number'] = $this->generatePoNumber();

            return PurchaseOrder::create($validated);
        });
    }

    public function update(PurchaseOrder $purchaseOrder, array $validated): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrder, $validated): PurchaseOrder {
            $purchaseOrder->update($validated);

            return $purchaseOrder->refresh()->load(['preparedBy']);
        });
    }

    public function destroy(PurchaseOrder $purchaseOrder): void
    {
        DB::transaction(function () use ($purchaseOrder): void { $purchaseOrder->delete(); });
    }

    private function generatePoNumber(): string
    {
        $year  = now()->format('Y');
        $count = PurchaseOrder::whereYear('created_at', $year)->count() + 1;

        return sprintf('PO-%s-%04d', $year, $count);
    }
}
