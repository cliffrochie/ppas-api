<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUserRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

final class UserController extends Controller
{
    public function __construct(private readonly UserService $service)
    {
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $paginator = $this->service->list();

        return response()->json([
            'data' => UserResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Users retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->store($request->validated());

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created successfully.',
            'errors' => null,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user = $this->service->show($user);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User retrieved successfully.',
            'errors' => null,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->service->update($user, $request->validated());

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User updated successfully.',
            'errors' => null,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->service->destroy($user);

        return response()->json([
            'data' => null,
            'message' => 'User deleted successfully.',
            'errors' => null,
        ]);
    }
}
