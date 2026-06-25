<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrStatusHistoryResource;
use App\Models\PrStatusHistory;
use App\Services\PrStatusHistoryService;
use Illuminate\Http\JsonResponse;

final class PrStatusHistoryController extends Controller
{
    public function __construct(private readonly PrStatusHistoryService $service)
    {
    }

    // Read-only: index + show only (append-only audit log)

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PrStatusHistory::class);

        $paginator = $this->service->list();

        return response()->json([
            'data' => PrStatusHistoryResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'PR status histories retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function show(PrStatusHistory $prStatusHistory): JsonResponse
    {
        $this->authorize('view', $prStatusHistory);

        $prStatusHistory = $this->service->show($prStatusHistory);

        return response()->json([
            'data' => new PrStatusHistoryResource($prStatusHistory),
            'message' => 'PR status history retrieved successfully.',
            'errors' => null,
        ]);
    }
}
