<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PurchaseOrderService
{
    public function list(User $user): LengthAwarePaginator
    {
        // PO list access is not role-scoped — all authorised roles see the full list.
        // The User parameter is accepted for consistency with PurchaseRequestService
        // and to allow role-based scoping to be added here without a signature change.
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
        // Must be called inside a DB::transaction — lockForUpdate() is a no-op
        // outside a transaction and would produce duplicates under concurrency.
        $year = now()->year;

        $last = PurchaseOrder::whereYear('created_at', $year)
            ->whereNotNull('po_number')
            ->lockForUpdate()
            ->max('po_number');

        $next = $last !== null ? ((int) substr($last, -5)) + 1 : 1;

        return sprintf('PO-%d-%05d', $year, $next);
    }
}
