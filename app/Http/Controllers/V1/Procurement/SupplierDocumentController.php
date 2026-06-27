<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreSupplierDocumentRequest;
use App\Http\Resources\SupplierDocumentResource;
use App\Models\SupplierDocument;
use App\Services\SupplierDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SupplierDocumentController extends Controller
{
    public function __construct(private readonly SupplierDocumentService $service)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', SupplierDocument::class);

        $paginator = $this->service->list();

        return response()->json([
            'data' => SupplierDocumentResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'message' => 'Supplier documents retrieved successfully.',
            'errors'  => null,
        ]);
    }

    public function store(StoreSupplierDocumentRequest $request): JsonResponse
    {
        $document = $this->service->store($request->validated());

        return response()->json([
            'data'    => new SupplierDocumentResource($document),
            'message' => 'Supplier document uploaded successfully.',
            'errors'  => null,
        ], 201);
    }

    public function show(SupplierDocument $supplierDocument): JsonResponse
    {
        $this->authorize('view', $supplierDocument);

        $supplierDocument = $this->service->show($supplierDocument);

        return response()->json([
            'data'    => new SupplierDocumentResource($supplierDocument),
            'message' => 'Supplier document retrieved successfully.',
            'errors'  => null,
        ]);
    }

    public function destroy(SupplierDocument $supplierDocument): JsonResponse
    {
        $this->authorize('delete', $supplierDocument);

        $this->service->destroy($supplierDocument);

        return response()->json([
            'data'    => null,
            'message' => 'Supplier document deleted successfully.',
            'errors'  => null,
        ]);
    }

    /**
     * Serve a private supplier document through an authorized download.
     * The file is streamed from the private disk — never exposed via a public URL.
     */
    public function download(SupplierDocument $supplierDocument): StreamedResponse
    {
        $this->authorize('view', $supplierDocument);

        return Storage::disk('private')->download(
            $supplierDocument->file_path,
            $supplierDocument->file_name,
        );
    }
}
