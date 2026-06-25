<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbstractOfQuotation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class AbstractOfQuotationService
{
    public function list(): LengthAwarePaginator
    {
        return AbstractOfQuotation::with(['preparedBy', 'rfq'])
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
        DB::transaction(function () use ($abstract): void { $abstract->delete(); });
    }
}
