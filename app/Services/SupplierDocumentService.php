<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SupplierDocument;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class SupplierDocumentService
{
    public function __construct(private readonly Request $request) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return SupplierDocument::with(['uploader'])
            ->when($filters['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($filters['uploader_id'] ?? null, fn ($q, $v) => $q->where('uploader_id', $v))
            ->when($filters['mime_type'] ?? null, fn ($q, $v) => $q->where('mime_type', 'like', "%{$v}%"))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('uploaded_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('uploaded_at', '<=', $v))
            ->orderByDesc('uploaded_at')
            ->paginate(15);
    }

    public function show(SupplierDocument $document): SupplierDocument
    {
        return $document->load(['supplier', 'uploader']);
    }

    public function store(array $validated): SupplierDocument
    {
        $file = $this->request->file('file');
        $path = null;

        try {
            // Store the file on the private disk before opening the transaction.
            // If the DB write fails, the catch block cleans it up.
            $path = $file->store(
                "supplier-documents/{$validated['supplier_id']}",
                'private',
            );

            return DB::transaction(function () use ($validated, $file, $path): SupplierDocument {
                return SupplierDocument::create([
                    'supplier_id' => $validated['supplier_id'],
                    'uploader_id' => $this->request->user()->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            // If the DB write failed after the file was stored, delete the orphan file.
            if ($path !== null) {
                Storage::disk('private')->delete($path);
            }

            throw $e;
        }
    }

    public function destroy(SupplierDocument $document): void
    {
        $path = $document->file_path;

        // Delete the DB record first — inside a transaction.
        // Orphaned files on disk are recoverable; phantom DB records are not.
        DB::transaction(function () use ($document): void {
            $document->delete();
        });

        // Delete the file after the DB commit succeeds.
        // A storage failure here does not roll back the DB delete.
        if ($path !== null) {
            Storage::disk('private')->delete($path);
        }
    }
}
