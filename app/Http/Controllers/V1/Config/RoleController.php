<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\Config\ListRoleRequest;
use App\Http\Requests\Config\StoreRoleRequest;
use App\Http\Requests\Config\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;

final class RoleController extends Controller
{
    public function __construct(private readonly RoleService $service) {}

    public function index(ListRoleRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $paginator = $this->service->list($request->validated());

        return response()->json([
            'data' => RoleResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Roles retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->service->store($request->validated());

        return response()->json([
            'data' => new RoleResource($role),
            'message' => 'Role created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return response()->json([
            'data' => new RoleResource($role),
            'message' => 'Role retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $role = $this->service->update($role, $request->validated());

        return response()->json([
            'data' => new RoleResource($role),
            'message' => 'Role updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->service->destroy($role);

        return response()->json([
            'data' => null,
            'message' => 'Role deleted successfully.',
            'errors' => null,
        ]);
    }
}
