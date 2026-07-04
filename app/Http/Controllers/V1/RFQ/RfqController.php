<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\RFQ;

use App\Http\Controllers\Controller;
use App\Http\Requests\RFQ\ListRfqRequest;
use App\Http\Requests\RFQ\StoreRfqRequest;
use App\Http\Requests\RFQ\UpdateRfqRequest;
use App\Http\Resources\RfqResource;
use App\Models\Rfq;
use App\Services\RfqService;
use Illuminate\Http\JsonResponse;

final class RfqController extends Controller
{
    public function __construct(private readonly RfqService $service) {}

    public function index(ListRfqRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Rfq::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => RfqResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'RFQs retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreRfqRequest $request): JsonResponse
    {
        $rfq = $this->service->store($request->validated());

        return response()->json([
            'data' => new RfqResource($rfq),
            'message' => 'RFQ created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(Rfq $rfq): JsonResponse
    {
        $this->authorize('view', $rfq);

        $rfq = $this->service->show($rfq);

        return response()->json([
            'data' => new RfqResource($rfq),
            'message' => 'RFQ retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateRfqRequest $request, Rfq $rfq): JsonResponse
    {
        $rfq = $this->service->update($rfq, $request->validated());

        return response()->json([
            'data' => new RfqResource($rfq),
            'message' => 'RFQ updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(Rfq $rfq): JsonResponse
    {
        $this->authorize('delete', $rfq);

        $this->service->destroy($rfq);

        return response()->json([
            'data' => null,
            'message' => 'RFQ deleted successfully.',
            'errors' => null,
        ]);
    }
}
