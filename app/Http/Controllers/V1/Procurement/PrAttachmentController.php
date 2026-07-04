<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ListPrAttachmentRequest;
use App\Http\Requests\Procurement\StorePrAttachmentRequest;
use App\Http\Requests\Procurement\UpdatePrAttachmentRequest;
use App\Http\Resources\PrAttachmentResource;
use App\Models\PrAttachment;
use App\Services\PrAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PrAttachmentController extends Controller
{
    public function __construct(private readonly PrAttachmentService $service) {}

    public function index(ListPrAttachmentRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PrAttachment::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => PrAttachmentResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'PR attachments retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StorePrAttachmentRequest $request): JsonResponse
    {
        $attachment = $this->service->store($request->validated());

        return response()->json([
            'data' => new PrAttachmentResource($attachment),
            'message' => 'PR attachment created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(PrAttachment $prAttachment): JsonResponse
    {
        $this->authorize('view', $prAttachment);

        $prAttachment = $this->service->show($prAttachment);

        return response()->json([
            'data' => new PrAttachmentResource($prAttachment),
            'message' => 'PR attachment retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdatePrAttachmentRequest $request, PrAttachment $prAttachment): JsonResponse
    {
        $attachment = $this->service->update($prAttachment, $request->validated());

        return response()->json([
            'data' => new PrAttachmentResource($attachment),
            'message' => 'PR attachment updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(PrAttachment $prAttachment): JsonResponse
    {
        $this->authorize('delete', $prAttachment);

        $this->service->destroy($prAttachment);

        return response()->json([
            'data' => null,
            'message' => 'PR attachment deleted successfully.',
            'errors' => null,
        ]);
    }

    /**
     * Serve a private attachment file through an authorized download.
     * The `view` policy check ensures only permitted users can access the file.
     * The file is streamed from the private disk — never exposed via a public URL.
     */
    public function download(PrAttachment $prAttachment): StreamedResponse
    {
        $this->authorize('view', $prAttachment);

        return Storage::disk('private')->download(
            $prAttachment->file_path,
            $prAttachment->file_name,
        );
    }
}
