<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Monitoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitoring\ListLoginLogRequest;
use App\Http\Resources\LoginLogResource;
use App\Models\LoginLog;
use App\Services\LoginLogService;
use Illuminate\Http\JsonResponse;

final class LoginLogController extends Controller
{
    public function __construct(private readonly LoginLogService $service) {}

    // Read-only: index + show only (append-only log)

    public function index(ListLoginLogRequest $request): JsonResponse
    {
        $this->authorize('viewAny', LoginLog::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => LoginLogResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Login logs retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function show(LoginLog $loginLog): JsonResponse
    {
        $this->authorize('view', $loginLog);

        $loginLog = $this->service->show($loginLog);

        return response()->json([
            'data' => new LoginLogResource($loginLog),
            'message' => 'Login log retrieved successfully.',
            'errors' => null,
        ]);
    }
}
