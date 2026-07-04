<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Monitoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitoring\ListNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

final class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $service) {}

    // No store/update — notifications are system-generated and immutable once created.
    // index + show + mark-read only.

    public function index(ListNotificationRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => NotificationResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Notifications retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function show(Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        return response()->json([
            'data' => new NotificationResource($notification),
            'message' => 'Notification retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification = $this->service->markRead($notification);

        return response()->json([
            'data' => new NotificationResource($notification),
            'message' => 'Notification marked as read.',
            'errors' => null,
        ]);
    }
}
