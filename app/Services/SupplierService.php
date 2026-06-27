<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class SupplierService
{
    public function __construct(private readonly Request $request) {}

    public function list(): LengthAwarePaginator
    {
        return Supplier::with(['category'])
            ->orderBy('name')
            ->paginate(15);
    }

    public function show(Supplier $supplier): Supplier
    {
        return $supplier->load(['category', 'documents.uploader']);
    }

    public function store(array $validated): Supplier
    {
        $logoPath = null;
        $file = $this->request->file('logo');

        try {
            if ($file !== null) {
                $logoPath = $file->store('supplier-logos', 'private');
            }

            return DB::transaction(function () use ($validated, $logoPath): Supplier {
                // logo is a file upload key — never a DB column; strip it before creating.
                unset($validated['logo']);

                return Supplier::create([
                    ...$validated,
                    'logo_path' => $logoPath,
                ]);
            });
        } catch (\Throwable $e) {
            // Orphan file cleanup: if the DB write fails, remove the uploaded logo.
            if ($logoPath !== null) {
                Storage::disk('private')->delete($logoPath);
            }

            throw $e;
        }
    }

    public function update(Supplier $supplier, array $validated): Supplier
    {
        $newLogoPath = null;
        $oldLogoPath = $supplier->logo_path;
        $file = $this->request->file('logo');

        try {
            if ($file !== null) {
                $newLogoPath = $file->store('supplier-logos', 'private');
            }

            $result = DB::transaction(function () use ($supplier, $validated, $newLogoPath): Supplier {
                unset($validated['logo']);

                $data = $newLogoPath !== null
                    ? array_merge($validated, ['logo_path' => $newLogoPath])
                    : $validated;

                $supplier->update($data);

                return $supplier->refresh()->load(['category']);
            });

            // Delete the old logo only after the DB commit succeeds.
            // A storage failure here does not roll back the DB change.
            if ($newLogoPath !== null && $oldLogoPath !== null) {
                Storage::disk('private')->delete($oldLogoPath);
            }

            return $result;
        } catch (\Throwable $e) {
            // If the DB write failed, remove the newly uploaded logo to avoid orphans.
            if ($newLogoPath !== null) {
                Storage::disk('private')->delete($newLogoPath);
            }

            throw $e;
        }
    }

    public function destroy(Supplier $supplier): void
    {
        $logoPath = $supplier->logo_path;

        // Delete the DB record first — inside a transaction.
        // Orphaned files on disk are recoverable; phantom DB records are not.
        DB::transaction(function () use ($supplier): void {
            $supplier->delete();
        });

        // Delete the logo after the DB commit succeeds.
        if ($logoPath !== null) {
            Storage::disk('private')->delete($logoPath);
        }
    }
}
