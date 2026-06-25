<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\RFQ;

use App\Http\Controllers\Controller;
use App\Http\Requests\RFQ\StoreCanvassResponseRequest;
use App\Http\Requests\RFQ\UpdateCanvassResponseRequest;
use App\Http\Resources\CanvassResponseResource;
use App\Models\CanvassResponse;
use App\Services\CanvassResponseService;
use Illuminate\Http\JsonResponse;

final class CanvassResponseController extends Controller
{
    public function __construct(private readonly CanvassResponseService $service)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', CanvassResponse::class);

        $paginator = $this->service->list();

        return response()->json([
            'data' => CanvassResponseResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Canvass responses retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreCanvassResponseRequest $request): JsonResponse
    {
        $response = $this->service->store($request->validated());

        return response()->json([
            'data' => new CanvassResponseResource($response),
            'message' => 'Canvass response created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(CanvassResponse $canvassResponse): JsonResponse
    {
        $this->authorize('view', $canvassResponse);

        return response()->json([
            'data' => new CanvassResponseResource($canvassResponse),
            'message' => 'Canvass response retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateCanvassResponseRequest $request, CanvassResponse $canvassResponse): JsonResponse
    {
        $response = $this->service->update($canvassResponse, $request->validated());

        return response()->json([
            'data' => new CanvassResponseResource($response),
            'message' => 'Canvass response updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(CanvassResponse $canvassResponse): JsonResponse
    {
        $this->authorize('delete', $canvassResponse);

        $this->service->destroy($canvassResponse);

        return response()->json([
            'data' => null,
            'message' => 'Canvass response deleted successfully.',
            'errors' => null,
        ]);
    }
}
