<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    private function procurementOfficer(): User
    {
        $role = Role::where('name', 'procurement_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function requester(): User
    {
        $role = Role::where('name', 'requester')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/users — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/users')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_users_for_any_role(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/users — store
    // -------------------------------------------------------------------------

    public function test_store_creates_user_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $requesterRole = Role::where('name', 'requester')->firstOrFail();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/users', [
                'first_name'            => 'John',
                'last_name'             => 'Doe',
                'email'                 => 'new.user@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'role_id'               => $requesterRole->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.email', 'new.user@example.com')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_403_for_requester_role(): void
    {
        $requester = $this->requester();
        $requesterRole = Role::where('name', 'requester')->firstOrFail();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/users', [
                'first_name'            => 'John',
                'last_name'             => 'Doe',
                'email'                 => 'blocked@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'role_id'               => $requesterRole->id,
            ])
            ->assertStatus(403);
    }

    public function test_store_returns_422_when_email_is_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/users', [
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'password'   => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/users/{user} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_user_for_authenticated_user(): void
    {
        $user = $this->requester();
        $target = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/users/{$target->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_user(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/users/{user} — update
    // -------------------------------------------------------------------------

    public function test_update_succeeds_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $target = User::factory()->create();

        $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/users/{$target->id}", [
                'first_name' => 'Updated',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.first_name', 'Updated');
    }

    public function test_update_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $target = User::factory()->create();

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/users/{$target->id}", [
                'first_name' => 'Hacked',
            ])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/users/{user} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_succeeds_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $target = User::factory()->create();

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/users/{$target->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);
    }

    public function test_destroy_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $target = User::factory()->create();

        $this->actingAs($requester, 'sanctum')
            ->deleteJson("/api/v1/users/{$target->id}")
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Response envelope — sensitive fields must not be exposed
    // -------------------------------------------------------------------------

    public function test_user_resource_never_exposes_password_or_token(): void
    {
        $user = $this->requester();
        $target = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/users/{$target->id}");

        $userData = $response->json('data');
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
    }
}
