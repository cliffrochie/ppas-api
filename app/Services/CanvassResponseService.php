<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CanvassResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CanvassResponseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return CanvassResponse::query()
            ->when($filters['rfq_id'] ?? null, fn ($q, $v) => $q->where('rfq_id', $v))
            ->when($filters['rfq_item_id'] ?? null, fn ($q, $v) => $q->where('rfq_item_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('supplier_name', 'like', "%{$v}%"))
            ->latest()
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
