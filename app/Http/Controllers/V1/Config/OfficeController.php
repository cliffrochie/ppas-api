<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Config\ListOfficeRequest;
use App\Http\Requests\Config\StoreOfficeRequest;
use App\Http\Requests\Config\UpdateOfficeRequest;
use App\Http\Resources\OfficeResource;
use App\Models\Office;
use App\Services\OfficeService;
use Illuminate\Http\JsonResponse;

final class OfficeController extends Controller
{
    public function __construct(private readonly OfficeService $service) {}

    public function index(ListOfficeRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Office::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => OfficeResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Offices retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreOfficeRequest $request): JsonResponse
    {
        $office = $this->service->store($request->validated());

        return response()->json([
            'data' => new OfficeResource($office),
            'message' => 'Office created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(Office $office): JsonResponse
    {
        $this->authorize('view', $office);

        return response()->json([
            'data' => new OfficeResource($office),
            'message' => 'Office retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateOfficeRequest $request, Office $office): JsonResponse
    {
        $office = $this->service->update($office, $request->validated());

        return response()->json([
            'data' => new OfficeResource($office),
            'message' => 'Office updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(Office $office): JsonResponse
    {
        $this->authorize('delete', $office);

        $this->service->destroy($office);

        return response()->json([
            'data' => null,
            'message' => 'Office deleted successfully.',
            'errors' => null,
        ]);
    }
}
