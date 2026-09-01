<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CanvassResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CanvassResponseService
{
    private const ALLOWED_SORTS = [
        'id', 'rfq_id', 'rfq_item_id', 'supplier_name', 'unit_price', 'total_price', 'created_at', 'updated_at',
    ];

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return CanvassResponse::query()
            ->when($filters['rfq_id'] ?? null, fn ($q, $v) => $q->where('rfq_id', $v))
            ->when($filters['rfq_item_id'] ?? null, fn ($q, $v) => $q->where('rfq_item_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('supplier_name', 'like', "%{$v}%"))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->latest()
            )
            ->paginate(15);
    }

    public function store(array $validated): CanvassResponse
    {
        return DB::transaction(fn (): CanvassResponse => CanvassResponse::create($validated));
    }

    public function update(CanvassResponse $response, array $validated): CanvassResponse
    {
        return DB::transaction(function () use ($response, $validated): CanvassResponse {
            $response->update($validated);

            return $response->refresh();
        });
    }

    public function destroy(CanvassResponse $response): void
    {
        DB::transaction(function () use ($response): void {
            $response->delete();
        });
    }
}
