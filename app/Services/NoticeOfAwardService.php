<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NoticeOfAward;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class NoticeOfAwardService
{
    private const ALLOWED_SORTS = [
        'id', 'noa_number', 'bac_resolution_id', 'awarded_supplier', 'awarded_amount', 'issued_at', 'created_at', 'updated_at',
    ];

    public function __construct(private readonly Request $request) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return NoticeOfAward::with(['bacResolution'])
            ->when($filters['bac_resolution_id'] ?? null, fn ($q, $v) => $q->where('bac_resolution_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($q) => $q->where('awarded_supplier', 'like', "%{$v}%")
                    ->orWhere('noa_number', 'like', "%{$v}%")
            ))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '<=', $v))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->latest()
            )
            ->paginate(15);
    }

    public function show(NoticeOfAward $noa): NoticeOfAward
    {
        return $noa->load(['bacResolution']);
    }

    public function store(array $validated): NoticeOfAward
    {
        $file = $this->request->file('file');
        unset($validated['file']);

        // Store the file on the private disk before opening the transaction.
        // If the DB write fails, the catch block cleans it up.
        $path = $file->store("notices-of-award/{$validated['bac_resolution_id']}", 'private');

        try {
            return DB::transaction(function () use ($validated, $path): NoticeOfAward {
                $validated['file_path'] = $path;

                return NoticeOfAward::create($validated);
            });
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($path);

            throw $e;
        }
    }

    public function update(NoticeOfAward $noa, array $validated): NoticeOfAward
    {
        $file = $this->request->file('file');
        unset($validated['file']);

        if ($file === null) {
            return DB::transaction(function () use ($noa, $validated): NoticeOfAward {
                $noa->update($validated);

                return $noa->refresh();
            });
        }

        $oldPath = $noa->file_path;
        $newPath = $file->store("notices-of-award/{$noa->bac_resolution_id}", 'private');
        $validated['file_path'] = $newPath;

        try {
            $updated = DB::transaction(function () use ($noa, $validated): NoticeOfAward {
                $noa->update($validated);

                return $noa->refresh();
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

    public function destroy(NoticeOfAward $noa): void
    {
        $path = $noa->file_path;

        DB::transaction(function () use ($noa): void {
            $noa->delete();
        });

        if ($path !== null) {
            Storage::disk('private')->delete($path);
        }
    }
}
