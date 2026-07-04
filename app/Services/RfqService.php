<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rfq;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class RfqService
{
    public function __construct(
        private readonly Request $request,
        private readonly DocumentNumberService $numberGenerator,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return Rfq::with(['preparedBy', 'purchaseRequest'])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('rfq_number', 'like', "%{$v}%"))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['prepared_by_id'] ?? null, fn ($q, $v) => $q->where('prepared_by_id', $v))
            ->when($filters['purchase_request_id'] ?? null, fn ($q, $v) => $q->where('purchase_request_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('deadline', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('deadline', '<=', $v))
            ->latest()
            ->paginate(15);
    }

    public function show(Rfq $rfq): Rfq
    {
        return $rfq->load(['preparedBy', 'purchaseRequest', 'items']);
    }

    public function store(array $validated): Rfq
    {
        $file = $this->request->file('file');
        unset($validated['file']);
        $path = null;

        try {
            if ($file !== null) {
                // Store the file on the private disk before opening the transaction.
                // If the DB write fails, the catch block cleans it up.
                $path = $file->store("rfqs/{$validated['purchase_request_id']}", 'private');
            }

            return DB::transaction(function () use ($validated, $path): Rfq {
                // rfq_number is auto-generated — never comes from user input
                $validated['rfq_number'] = $this->numberGenerator->generate('RFQ');
                $validated['file_path'] = $path;

                return Rfq::create($validated);
            });
        } catch (\Throwable $e) {
            // If the DB write failed after the file was stored, delete the orphan file.
            if ($path !== null) {
                Storage::disk('private')->delete($path);
            }

            throw $e;
        }
    }

    public function update(Rfq $rfq, array $validated): Rfq
    {
        $file = $this->request->file('file');
        unset($validated['file']);

        if ($file === null) {
            return DB::transaction(function () use ($rfq, $validated): Rfq {
                $rfq->update($validated);

                return $rfq->refresh()->load(['preparedBy']);
            });
        }

        $oldPath = $rfq->file_path;

        // Store the new file before opening the transaction.
        // If the DB write fails, the catch block cleans up the new file.
        $newPath = $file->store("rfqs/{$rfq->purchase_request_id}", 'private');
        $validated['file_path'] = $newPath;

        try {
            $updated = DB::transaction(function () use ($rfq, $validated): Rfq {
                $rfq->update($validated);

                return $rfq->refresh()->load(['preparedBy']);
            });
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($newPath);

            throw $e;
        }

        // Delete the replaced file only after the DB commit succeeds.
        if ($oldPath !== null) {
            Storage::disk('private')->delete($oldPath);
        }

        return $updated;
    }

    public function destroy(Rfq $rfq): void
    {
        $path = $rfq->file_path;

        // Delete the DB record first — inside a transaction.
        // Orphaned files on disk are recoverable; phantom DB records are not.
        DB::transaction(function () use ($rfq): void {
            $rfq->delete();
        });

        // Delete the file after the DB commit succeeds.
        if ($path !== null) {
            Storage::disk('private')->delete($path);
        }
    }
}
