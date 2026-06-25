<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StorePurchaseRequestItemRequest;
use App\Http\Requests\Procurement\UpdatePurchaseRequestItemRequest;
use App\Http\Resources\PurchaseRequestItemResource;
use App\Models\PurchaseRequestItem;
use App\Services\PurchaseRequestItemService;
use Illuminate\Http\JsonResponse;

final class PurchaseRequestItemController extends Controller
{
    public function __construct(private readonly PurchaseRequestItemService $service)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PurchaseRequestItem::class);

        $paginator = $this->service->list();

        return response()->json([
            'data' => PurchaseRequestItemResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Purchase request items retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StorePurchaseRequestItemRequest $request): JsonResponse
    {
        $item = $this->service->store($request->validated());

        return response()->json([
            'data' => new PurchaseRequestItemResource($item),
            'message' => 'Purchase request item created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(PurchaseRequestItem $purchaseRequestItem): JsonResponse
    {
        $this->authorize('view', $purchaseRequestItem);

        return response()->json([
            'data' => new PurchaseRequestItemResource($purchaseRequestItem),
            'message' => 'Purchase request item retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdatePurchaseRequestItemRequest $request, PurchaseRequestItem $purchaseRequestItem): JsonResponse
    {
        $item = $this->service->update($purchaseRequestItem, $request->validated());

        return response()->json([
            'data' => new PurchaseRequestItemResource($item),
            'message' => 'Purchase request item updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(PurchaseRequestItem $purchaseRequestItem): JsonResponse
    {
        $this->authorize('delete', $purchaseRequestItem);

        $this->service->destroy($purchaseRequestItem);

        return response()->json([
            'data' => null,
            'message' => 'Purchase request item deleted successfully.',
            'errors' => null,
        ]);
    }
}
