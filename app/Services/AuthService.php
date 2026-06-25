<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class AuthService
{
    /**
     * Attempt login and return a Sanctum plain-text token on success.
     * Returns null when credentials are invalid.
     *
     * @return array{token: string, user: User}|null
     */
    public function login(string $email, string $password): ?array
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            return null;
        }

        /** @var User $user */
        $user = Auth::user();

        // Load relationships needed for the UserResource response
        $user->load(['role', 'office']);

        $token = $user->createToken('api')->plainTextToken;

        return [
            'token' => $token,
            'user'  => $user,
        ];
    }
}
