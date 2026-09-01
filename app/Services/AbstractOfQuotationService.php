<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AbstractOfQuotation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class AbstractOfQuotationService
{
    private const ALLOWED_SORTS = [
        'id', 'rfq_id', 'prepared_by_id', 'recommended_supplier', 'recommended_amount', 'status', 'approved_at', 'created_at', 'updated_at',
    ];

    public function __construct(private readonly Request $request) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return AbstractOfQuotation::with(['preparedBy', 'rfq'])
            ->when($filters['rfq_id'] ?? null, fn ($q, $v) => $q->where('rfq_id', $v))
            ->when($filters['prepared_by_id'] ?? null, fn ($q, $v) => $q->where('prepared_by_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('recommended_supplier', 'like', "%{$v}%"))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->latest()
            )
            ->paginate(15);
    }

    public function show(AbstractOfQuotation $abstract): AbstractOfQuotation
    {
        return $abstract->load(['preparedBy', 'rfq']);
    }

    public function store(array $validated): AbstractOfQuotation
    {
        $file = $this->request->file('file');
        unset($validated['file']);
        $path = null;

        try {
            if ($file !== null) {
                $path = $file->store("abstracts-of-quotation/{$validated['rfq_id']}", 'private');
            }

            return DB::transaction(function () use ($validated, $path): AbstractOfQuotation {
                $validated['file_path'] = $path;

                return AbstractOfQuotation::create($validated);
            });
        } catch (\Throwable $e) {
            if ($path !== null) {
                Storage::disk('private')->delete($path);
            }

            throw $e;
        }
    }

    public function update(AbstractOfQuotation $abstract, array $validated): AbstractOfQuotation
    {
        $file = $this->request->file('file');
        unset($validated['file']);

        if ($file === null) {
            return DB::transaction(function () use ($abstract, $validated): AbstractOfQuotation {
                $abstract->update($validated);

                return $abstract->refresh()->load(['preparedBy']);
            });
        }

        $oldPath = $abstract->file_path;
        $newPath = $file->store("abstracts-of-quotation/{$abstract->rfq_id}", 'private');
        $validated['file_path'] = $newPath;

        try {
            $updated = DB::transaction(function () use ($abstract, $validated): AbstractOfQuotation {
                $abstract->update($validated);

                return $abstract->refresh()->load(['preparedBy']);
            });
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($newPath);

            throw $e;
        }

        if ($oldPath !== null) {
            Storage::disk('private')->delete($oldPath);
        }

        return $updated;
    }

    public function destroy(AbstractOfQuotation $abstract): void
    {
        $path = $abstract->file_path;

        DB::transaction(function () use ($abstract): void {
            $abstract->delete();
        });

        if ($path !== null) {
            Storage::disk('private')->delete($path);
        }
    }
}
