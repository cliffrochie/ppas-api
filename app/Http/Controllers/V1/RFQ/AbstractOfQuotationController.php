<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\RFQ;

use App\Http\Controllers\Controller;
use App\Http\Requests\RFQ\StoreAbstractOfQuotationRequest;
use App\Http\Requests\RFQ\UpdateAbstractOfQuotationRequest;
use App\Http\Resources\AbstractOfQuotationResource;
use App\Models\AbstractOfQuotation;
use App\Services\AbstractOfQuotationService;
use Illuminate\Http\JsonResponse;

final class AbstractOfQuotationController extends Controller
{
    public function __construct(private readonly AbstractOfQuotationService $service)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AbstractOfQuotation::class);

        $paginator = $this->service->list();

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
}
