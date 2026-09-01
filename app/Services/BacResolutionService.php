<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BacResolution;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class BacResolutionService
{
    private const ALLOWED_SORTS = [
        'id', 'resolution_number', 'abstract_of_quotation_id', 'prepared_by_id', 'issued_at', 'created_at', 'updated_at',
    ];

    public function __construct(private readonly Request $request) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return BacResolution::with(['preparedBy', 'abstractOfQuotation'])
            ->when($filters['abstract_of_quotation_id'] ?? null, fn ($q, $v) => $q->where('abstract_of_quotation_id', $v))
            ->when($filters['prepared_by_id'] ?? null, fn ($q, $v) => $q->where('prepared_by_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->search($v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '<=', $v))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->latest()
            )
            ->paginate(15);
    }

    public function show(BacResolution $resolution): BacResolution
    {
        return $resolution->load(['preparedBy', 'abstractOfQuotation']);
    }

    public function store(array $validated): BacResolution
    {
        $file = $this->request->file('file');
        unset($validated['file']);

        // Store the file on the private disk before opening the transaction.
        // If the DB write fails, the catch block cleans it up.
        $path = $file->store("bac-resolutions/{$validated['abstract_of_quotation_id']}", 'private');

        try {
            return DB::transaction(function () use ($validated, $path): BacResolution {
                $validated['file_path'] = $path;

                return BacResolution::create($validated);
            });
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($path);

            throw $e;
        }
    }

    public function update(BacResolution $resolution, array $validated): BacResolution
    {
        $file = $this->request->file('file');
        unset($validated['file']);

        if ($file === null) {
            return DB::transaction(function () use ($resolution, $validated): BacResolution {
                $resolution->update($validated);

                return $resolution->refresh()->load(['preparedBy']);
            });
        }

        $oldPath = $resolution->file_path;
        $newPath = $file->store("bac-resolutions/{$resolution->abstract_of_quotation_id}", 'private');
        $validated['file_path'] = $newPath;

        try {
            $updated = DB::transaction(function () use ($resolution, $validated): BacResolution {
                $resolution->update($validated);

                return $resolution->refresh()->load(['preparedBy']);
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

    public function destroy(BacResolution $resolution): void
    {
        $path = $resolution->file_path;

        DB::transaction(function () use ($resolution): void {
            $resolution->delete();
        });

        if ($path !== null) {
            Storage::disk('private')->delete($path);
        }
    }
}
