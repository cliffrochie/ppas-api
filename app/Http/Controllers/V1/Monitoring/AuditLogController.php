<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Monitoring;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;

final class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $service)
    {
    }

    // Read-only: index + show only (append-only log)

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $paginator = $this->service->list();

        return response()->json([
            'data' => AuditLogResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Audit logs retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        $this->authorize('view', $auditLog);

        $auditLog = $this->service->show($auditLog);

        return response()->json([
            'data' => new AuditLogResource($auditLog),
            'message' => 'Audit log retrieved successfully.',
            'errors' => null,
        ]);
    }
}
