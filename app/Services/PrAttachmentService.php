<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PrAttachment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class PrAttachmentService
{
    public function __construct(private readonly Request $request) {}

    public function list(): LengthAwarePaginator
    {
        return PrAttachment::with(['uploader'])
            ->orderByDesc('uploaded_at')
            ->paginate(15);
    }

    public function show(PrAttachment $attachment): PrAttachment
    {
        return $attachment->load(['uploader']);
    }

    public function store(array $validated): PrAttachment
    {
        $file = $this->request->file('file');
        $path = null;

        try {
            // Store the file on the private disk before opening the transaction.
            // If the DB write fails, the catch block cleans it up.
            $path = $file->store(
                "pr-attachments/{$validated['purchase_request_id']}",
                'private',
            );

            return DB::transaction(function () use ($validated, $file, $path): PrAttachment {
                return PrAttachment::create([
                    'purchase_request_id' => $validated['purchase_request_id'],
                    'uploader_id'         => $this->request->user()->id,
                    'type'                => $validated['type'],
                    'file_name'           => $file->getClientOriginalName(),
                    'file_path'           => $path,
                    'file_size'           => $file->getSize(),
                    'mime_type'           => $file->getMimeType(),
                    'uploaded_at'         => now(),
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

    public function update(PrAttachment $attachment, array $validated): PrAttachment
    {
        return DB::transaction(function () use ($attachment, $validated): PrAttachment {
            $attachment->update($validated);

            return $attachment->refresh()->load(['uploader']);
        });
    }

    public function destroy(PrAttachment $attachment): void
    {
        $path = $attachment->file_path;

        // Delete the DB record first — inside a transaction.
        // Orphaned files on disk are recoverable; phantom DB records are not.
        DB::transaction(function () use ($attachment): void {
            $attachment->delete();
        });

        // Delete the file after the DB commit succeeds.
        // A storage failure here does not roll back the DB delete.
        if ($path !== null) {
            Storage::disk('private')->delete($path);
        }
    }
}
