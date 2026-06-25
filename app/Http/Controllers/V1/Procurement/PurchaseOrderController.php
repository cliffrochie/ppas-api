<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StorePurchaseOrderRequest;
use App\Http\Requests\Procurement\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;

final class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $paginator = $this->service->list();

        return response()->json([
            'data' => PurchaseOrderResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Purchase orders retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $purchaseOrder = $this->service->store($request->validated());

        return response()->json([
            'data' => new PurchaseOrderResource($purchaseOrder),
            'message' => 'Purchase order created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder = $this->service->show($purchaseOrder);

        return response()->json([
            'data' => new PurchaseOrderResource($purchaseOrder),
            'message' => 'Purchase order retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder = $this->service->update($purchaseOrder, $request->validated());

        return response()->json([
            'data' => new PurchaseOrderResource($purchaseOrder),
            'message' => 'Purchase order updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('delete', $purchaseOrder);

        $this->service->destroy($purchaseOrder);

        return response()->json([
            'data' => null,
            'message' => 'Purchase order deleted successfully.',
            'errors' => null,
        ]);
    }
}
