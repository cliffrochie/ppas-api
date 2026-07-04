<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\RFQ;

use App\Http\Controllers\Controller;
use App\Http\Requests\RFQ\ListRfqItemRequest;
use App\Http\Requests\RFQ\StoreRfqItemRequest;
use App\Http\Requests\RFQ\UpdateRfqItemRequest;
use App\Http\Resources\RfqItemResource;
use App\Models\RfqItem;
use App\Services\RfqItemService;
use Illuminate\Http\JsonResponse;

final class RfqItemController extends Controller
{
    public function __construct(private readonly RfqItemService $service) {}

    public function index(ListRfqItemRequest $request): JsonResponse
    {
        $this->authorize('viewAny', RfqItem::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => RfqItemResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'RFQ items retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreRfqItemRequest $request): JsonResponse
    {
        $item = $this->service->store($request->validated());

        return response()->json([
            'data' => new RfqItemResource($item),
            'message' => 'RFQ item created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(RfqItem $rfqItem): JsonResponse
    {
        $this->authorize('view', $rfqItem);

        return response()->json([
            'data' => new RfqItemResource($rfqItem),
            'message' => 'RFQ item retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateRfqItemRequest $request, RfqItem $rfqItem): JsonResponse
    {
        $item = $this->service->update($rfqItem, $request->validated());

        return response()->json([
            'data' => new RfqItemResource($item),
            'message' => 'RFQ item updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(RfqItem $rfqItem): JsonResponse
    {
        $this->authorize('delete', $rfqItem);

        $this->service->destroy($rfqItem);

        return response()->json([
            'data' => null,
            'message' => 'RFQ item deleted successfully.',
            'errors' => null,
        ]);
    }
}
