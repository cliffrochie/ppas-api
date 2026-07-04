<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
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
            'user' => $user,
        ];
    }

    /**
     * Create a new user account and issue a Sanctum token.
     *
     * New self-registered users are assigned the `requester` role — the lowest
     * privilege role in the system. Admins can promote the role afterward.
     *
     * @return array{token: string, user: User}
     */
    public function register(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        ?string $middleName = null,
        ?string $extensionName = null,
    ): array {
        // `requester` is the default self-registration role — lowest privilege.
        $requesterRole = Role::where('name', 'requester')->firstOrFail();

        $user = User::create([
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'extension_name' => $extensionName,
            'email' => $email,
            'password' => $password, // Auto-hashed via the model's `hashed` cast.
            'role_id' => $requesterRole->id,
            'is_active' => true,
        ]);

        // Load relationships so UserResource can include role and office data.
        $user->load(['role', 'office']);

        $token = $user->createToken('api')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
