<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a valid registration payload, allowing per-test overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/auth/register — happy path
    // -------------------------------------------------------------------------

    public function test_register_returns_201_with_token_and_user_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'email', 'first_name'],
                ],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.user.first_name', 'Jane')
            ->assertJsonPath('message', 'Registration successful.');

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_register_issues_a_usable_sanctum_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload());

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        // The issued token must authenticate successfully on a protected route.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200);
    }

    public function test_register_assigns_requester_role_by_default(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload());

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertEquals('requester', $user->role->name);
    }

    public function test_register_response_never_exposes_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload());

        $userData = $response->json('data.user');
        $this->assertArrayNotHasKey('password', $userData);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/auth/register — validation failures (422)
    // -------------------------------------------------------------------------

    public function test_register_returns_422_for_duplicate_email(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/v1/auth/register', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_register_returns_422_for_password_mismatch(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload([
            'password_confirmation' => 'does-not-match',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors.password.0', 'The password field confirmation does not match.');
    }

    public function test_register_succeeds_without_optional_name_fields(): void
    {
        // middle_name and extension_name are nullable — omitting them must still yield 201.
        $payload = $this->validPayload();
        unset($payload['middle_name'], $payload['extension_name']);

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.user.first_name', 'Jane');
    }

    public function test_register_returns_422_when_first_name_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['first_name']);

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.first_name.0', 'The first name field is required.');
    }

    public function test_register_returns_422_when_last_name_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['last_name']);

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.last_name.0', 'The last name field is required.');
    }

    public function test_register_returns_422_when_email_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['email']);

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    public function test_register_returns_422_when_password_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['password'], $payload['password_confirmation']);

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    public function test_register_returns_422_when_password_is_too_short(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]))
            ->assertStatus(422)
            ->assertJsonPath('data', null);
    }
}
