<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Resolution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resolution\ListBacResolutionRequest;
use App\Http\Requests\Resolution\StoreBacResolutionRequest;
use App\Http\Requests\Resolution\UpdateBacResolutionRequest;
use App\Http\Resources\BacResolutionResource;
use App\Models\BacResolution;
use App\Services\BacResolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BacResolutionController extends Controller
{
    public function __construct(private readonly BacResolutionService $service) {}

    public function index(ListBacResolutionRequest $request): JsonResponse
    {
        $this->authorize('viewAny', BacResolution::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => BacResolutionResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'BAC resolutions retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreBacResolutionRequest $request): JsonResponse
    {
        $resolution = $this->service->store($request->validated());

        return response()->json([
            'data' => new BacResolutionResource($resolution),
            'message' => 'BAC resolution created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(BacResolution $bacResolution): JsonResponse
    {
        $this->authorize('view', $bacResolution);

        $bacResolution = $this->service->show($bacResolution);

        return response()->json([
            'data' => new BacResolutionResource($bacResolution),
            'message' => 'BAC resolution retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateBacResolutionRequest $request, BacResolution $bacResolution): JsonResponse
    {
        $resolution = $this->service->update($bacResolution, $request->validated());

        return response()->json([
            'data' => new BacResolutionResource($resolution),
            'message' => 'BAC resolution updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(BacResolution $bacResolution): JsonResponse
    {
        $this->authorize('delete', $bacResolution);

        $this->service->destroy($bacResolution);

        return response()->json([
            'data' => null,
            'message' => 'BAC resolution deleted successfully.',
            'errors' => null,
        ]);
    }

    /**
     * Serve the resolution's uploaded document through an authorized download.
     * The file is streamed from the private disk — never exposed via a public URL.
     */
    public function download(BacResolution $bacResolution): StreamedResponse
    {
        $this->authorize('view', $bacResolution);

        return Storage::disk('private')->download($bacResolution->file_path);
    }
}
