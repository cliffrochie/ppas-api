<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    // -------------------------------------------------------------------------
    // POST /api/v1/auth/login
    // -------------------------------------------------------------------------

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'user' => ['id', 'email']],
                'message',
                'errors',
            ])
            ->assertJson(['errors' => null]);
    }

    public function test_login_returns_401_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nobody@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors', null);
    }

    public function test_login_returns_422_when_email_is_missing(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/auth/logout
    // -------------------------------------------------------------------------

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('api');
        $plainToken = $tokenResult->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->deleteJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors', null);

        // The token must have been deleted from the database.
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenResult->accessToken->id,
        ]);
    }

    public function test_logout_returns_401_without_token(): void
    {
        $this->deleteJson('/api/v1/auth/logout')
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/auth/me
    // -------------------------------------------------------------------------

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('errors', null);
    }

    public function test_me_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // Response envelope integrity
    // -------------------------------------------------------------------------

    public function test_login_response_never_exposes_password(): void
    {
        $user = User::factory()->create([
            'email'    => 'secure@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'secure@example.com',
            'password' => 'password',
        ]);

        $content = $response->json();
        $this->assertArrayNotHasKey('password', $content['data']['user']);
    }
}
