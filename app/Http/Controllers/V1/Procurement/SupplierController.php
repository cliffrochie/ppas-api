<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ListSupplierRequest;
use App\Http\Requests\Procurement\StoreSupplierRequest;
use App\Http\Requests\Procurement\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SupplierController extends Controller
{
    public function __construct(private readonly SupplierService $service) {}

    public function index(ListSupplierRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => SupplierResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Suppliers retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->service->store($request->validated());

        return response()->json([
            'data' => new SupplierResource($supplier),
            'message' => 'Supplier created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $supplier = $this->service->show($supplier);

        return response()->json([
            'data' => new SupplierResource($supplier),
            'message' => 'Supplier retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier = $this->service->update($supplier, $request->validated());

        return response()->json([
            'data' => new SupplierResource($supplier),
            'message' => 'Supplier updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $this->service->destroy($supplier);

        return response()->json([
            'data' => null,
            'message' => 'Supplier deleted successfully.',
            'errors' => null,
        ]);
    }

    /**
     * Serve the supplier logo through an authorized download.
     * The logo is stored on the private disk — never exposed via a public URL.
     */
    public function downloadLogo(Supplier $supplier): StreamedResponse
    {
        $this->authorize('view', $supplier);

        return Storage::disk('private')->download(
            $supplier->logo_path,
            basename($supplier->logo_path),
        );
    }
}
