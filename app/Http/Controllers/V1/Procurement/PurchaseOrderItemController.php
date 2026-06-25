<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StorePurchaseOrderItemRequest;
use App\Http\Requests\Procurement\UpdatePurchaseOrderItemRequest;
use App\Http\Resources\PurchaseOrderItemResource;
use App\Models\PurchaseOrderItem;
use App\Services\PurchaseOrderItemService;
use Illuminate\Http\JsonResponse;

final class PurchaseOrderItemController extends Controller
{
    public function __construct(private readonly PurchaseOrderItemService $service)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrderItem::class);

        $paginator = $this->service->list();

        return response()->json([
            'data' => PurchaseOrderItemResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Purchase order items retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StorePurchaseOrderItemRequest $request): JsonResponse
    {
        $item = $this->service->store($request->validated());

        return response()->json([
            'data' => new PurchaseOrderItemResource($item),
            'message' => 'Purchase order item created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(PurchaseOrderItem $purchaseOrderItem): JsonResponse
    {
        $this->authorize('view', $purchaseOrderItem);

        return response()->json([
            'data' => new PurchaseOrderItemResource($purchaseOrderItem),
            'message' => 'Purchase order item retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdatePurchaseOrderItemRequest $request, PurchaseOrderItem $purchaseOrderItem): JsonResponse
    {
        $item = $this->service->update($purchaseOrderItem, $request->validated());

        return response()->json([
            'data' => new PurchaseOrderItemResource($item),
            'message' => 'Purchase order item updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(PurchaseOrderItem $purchaseOrderItem): JsonResponse
    {
        $this->authorize('delete', $purchaseOrderItem);

        $this->service->destroy($purchaseOrderItem);

        return response()->json([
            'data' => null,
            'message' => 'Purchase order item deleted successfully.',
            'errors' => null,
        ]);
    }
}
