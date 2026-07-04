<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\RFQ;

use App\Http\Controllers\Controller;
use App\Http\Requests\RFQ\ListAbstractOfQuotationRequest;
use App\Http\Requests\RFQ\StoreAbstractOfQuotationRequest;
use App\Http\Requests\RFQ\UpdateAbstractOfQuotationRequest;
use App\Http\Resources\AbstractOfQuotationResource;
use App\Models\AbstractOfQuotation;
use App\Services\AbstractOfQuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AbstractOfQuotationController extends Controller
{
    public function __construct(private readonly AbstractOfQuotationService $service) {}

    public function index(ListAbstractOfQuotationRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AbstractOfQuotation::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => AbstractOfQuotationResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Abstracts of quotation retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreAbstractOfQuotationRequest $request): JsonResponse
    {
        $abstract = $this->service->store($request->validated());

        return response()->json([
            'data' => new AbstractOfQuotationResource($abstract),
            'message' => 'Abstract of quotation created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(AbstractOfQuotation $abstractOfQuotation): JsonResponse
    {
        $this->authorize('view', $abstractOfQuotation);

        $abstractOfQuotation = $this->service->show($abstractOfQuotation);

        return response()->json([
            'data' => new AbstractOfQuotationResource($abstractOfQuotation),
            'message' => 'Abstract of quotation retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateAbstractOfQuotationRequest $request, AbstractOfQuotation $abstractOfQuotation): JsonResponse
    {
        $abstract = $this->service->update($abstractOfQuotation, $request->validated());

        return response()->json([
            'data' => new AbstractOfQuotationResource($abstract),
            'message' => 'Abstract of quotation updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(AbstractOfQuotation $abstractOfQuotation): JsonResponse
    {
        $this->authorize('delete', $abstractOfQuotation);

        $this->service->destroy($abstractOfQuotation);

        return response()->json([
            'data' => null,
            'message' => 'Abstract of quotation deleted successfully.',
            'errors' => null,
        ]);
    }

    /**
     * Serve the abstract's uploaded document through an authorized download.
     * The file is streamed from the private disk — never exposed via a public URL.
     */
    public function download(AbstractOfQuotation $abstractOfQuotation): StreamedResponse
    {
        $this->authorize('view', $abstractOfQuotation);

        abort_if($abstractOfQuotation->file_path === null, 404);

        return Storage::disk('private')->download($abstractOfQuotation->file_path);
    }
}
