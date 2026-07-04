<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Config\ListCategoryRequest;
use App\Http\Requests\Config\StoreCategoryRequest;
use App\Http\Requests\Config\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $service) {}

    public function index(ListCategoryRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => CategoryResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Categories retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->service->store($request->validated());

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Category created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Category retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category = $this->service->update($category, $request->validated());

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Category updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $this->service->destroy($category);

        return response()->json([
            'data' => null,
            'message' => 'Category deleted successfully.',
            'errors' => null,
        ]);
    }
}
