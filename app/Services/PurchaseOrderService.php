<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PurchaseOrderService
{
    public function __construct(private readonly DocumentNumberService $numberGenerator) {}

    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        return PurchaseOrder::with(['preparedBy', 'purchaseRequest'])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($q) => $q->where('po_number', 'like', "%{$v}%")
                    ->orWhere('supplier_name', 'like', "%{$v}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['prepared_by_id'] ?? null, fn ($q, $v) => $q->where('prepared_by_id', $v))
            ->when($filters['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($filters['purchase_request_id'] ?? null, fn ($q, $v) => $q->where('purchase_request_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('delivery_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('delivery_date', '<=', $v))
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
            $validated['po_number'] = $this->numberGenerator->generate('PO');

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
        DB::transaction(function () use ($purchaseOrder): void {
            $purchaseOrder->delete();
        });
    }
}
