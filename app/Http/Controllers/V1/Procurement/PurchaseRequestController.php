<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StorePurchaseRequestRequest;
use App\Http\Requests\Procurement\UpdatePurchaseRequestRequest;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Illuminate\Http\JsonResponse;

final class PurchaseRequestController extends Controller
{
    public function __construct(private readonly PurchaseRequestService $service)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $paginator = $this->service->list();

        return response()->json([
            'data' => PurchaseRequestResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Purchase requests retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StorePurchaseRequestRequest $request): JsonResponse
    {
        $purchaseRequest = $this->service->store($request->validated());

        return response()->json([
            'data' => new PurchaseRequestResource($purchaseRequest),
            'message' => 'Purchase request created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorize('view', $purchaseRequest);

        $purchaseRequest = $this->service->show($purchaseRequest);

        return response()->json([
            'data' => new PurchaseRequestResource($purchaseRequest),
            'message' => 'Purchase request retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdatePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $purchaseRequest = $this->service->update($purchaseRequest, $request->validated());

        return response()->json([
            'data' => new PurchaseRequestResource($purchaseRequest),
            'message' => 'Purchase request updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorize('delete', $purchaseRequest);

        $this->service->destroy($purchaseRequest);

        return response()->json([
            'data' => null,
            'message' => 'Purchase request deleted successfully.',
            'errors' => null,
        ]);
    }
}
