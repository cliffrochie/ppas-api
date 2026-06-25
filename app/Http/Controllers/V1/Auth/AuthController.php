<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $service)
    {
    }

    /**
     * Authenticate the user and issue a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->service->login(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        if ($result === null) {
            return response()->json([
                'data' => null,
                'message' => 'Invalid credentials.',
                'errors' => null,
            ], 401);
        }

        return response()->json([
            'data' => [
                'token' => $result['token'],
                'user' => new UserResource($result['user']),
            ],
            'message' => 'Login successful.',
            'errors' => null,
        ]);
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'data' => null,
            'message' => 'Logged out successfully.',
            'errors' => null,
        ]);
    }

    /**
     * Return the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user()->load(['role', 'office']);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'Authenticated user retrieved.',
            'errors' => null,
        ]);
    }
}
