<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Resolution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resolution\ListNoticeOfAwardRequest;
use App\Http\Requests\Resolution\StoreNoticeOfAwardRequest;
use App\Http\Requests\Resolution\UpdateNoticeOfAwardRequest;
use App\Http\Resources\NoticeOfAwardResource;
use App\Models\NoticeOfAward;
use App\Services\NoticeOfAwardService;
use Illuminate\Http\JsonResponse;

final class NoticeOfAwardController extends Controller
{
    public function __construct(private readonly NoticeOfAwardService $service) {}

    public function index(ListNoticeOfAwardRequest $request): JsonResponse
    {
        $this->authorize('viewAny', NoticeOfAward::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => NoticeOfAwardResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Notices of award retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreNoticeOfAwardRequest $request): JsonResponse
    {
        $noa = $this->service->store($request->validated());

        return response()->json([
            'data' => new NoticeOfAwardResource($noa),
            'message' => 'Notice of award created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(NoticeOfAward $noticeOfAward): JsonResponse
    {
        $this->authorize('view', $noticeOfAward);

        $noticeOfAward = $this->service->show($noticeOfAward);

        return response()->json([
            'data' => new NoticeOfAwardResource($noticeOfAward),
            'message' => 'Notice of award retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateNoticeOfAwardRequest $request, NoticeOfAward $noticeOfAward): JsonResponse
    {
        $noa = $this->service->update($noticeOfAward, $request->validated());

        return response()->json([
            'data' => new NoticeOfAwardResource($noa),
            'message' => 'Notice of award updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(NoticeOfAward $noticeOfAward): JsonResponse
    {
        $this->authorize('delete', $noticeOfAward);

        $this->service->destroy($noticeOfAward);

        return response()->json([
            'data' => null,
            'message' => 'Notice of award deleted successfully.',
            'errors' => null,
        ]);
    }
}
