<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StorePurchaseRequestRequest;
use App\Http\Requests\Procurement\UpdatePurchaseRequestRequest;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Services\PdfService;
use App\Services\PurchaseRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PurchaseRequestController extends Controller
{
    public function __construct(
        private readonly PurchaseRequestService $service,
        private readonly PdfService $pdfService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseRequest::class);

        $paginator = $this->service->list($request->user());

        return response()->json([
            'data' => PurchaseRequestResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Purchase requests retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StorePurchaseRequestRequest $request): JsonResponse
    {
        $purchaseRequest = $this->service->store($request->validated());

        return response()->json([
            'data' => new PurchaseRequestResource($purchaseRequest),
            'message' => 'Purchase request created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorize('view', $purchaseRequest);

        $purchaseRequest = $this->service->show($purchaseRequest);

        return response()->json([
            'data' => new PurchaseRequestResource($purchaseRequest),
            'message' => 'Purchase request retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdatePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $purchaseRequest = $this->service->update($purchaseRequest, $request->validated());

        return response()->json([
            'data' => new PurchaseRequestResource($purchaseRequest),
            'message' => 'Purchase request updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorize('delete', $purchaseRequest);

        $this->service->destroy($purchaseRequest);

        return response()->json([
            'data' => null,
            'message' => 'Purchase request deleted successfully.',
            'errors' => null,
        ]);
    }

    /**
     * Download the NIA internal Request Form (RF) as a PDF.
     * Available once the PR has been submitted (rf_number assigned).
     */
    public function downloadRequestForm(PurchaseRequest $purchaseRequest): Response
    {
        $this->authorize('view', $purchaseRequest);

        return $this->pdfService->requestForm($purchaseRequest);
    }

    /**
     * Download the official NIA Purchase Request as a PDF.
     * Available once the PR has been approved (pr_number assigned).
     */
    public function downloadPurchaseRequest(PurchaseRequest $purchaseRequest): Response
    {
        $this->authorize('view', $purchaseRequest);

        return $this->pdfService->purchaseRequest($purchaseRequest);
    }
}
