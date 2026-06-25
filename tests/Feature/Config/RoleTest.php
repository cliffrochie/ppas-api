<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RoleTest extends TestCase
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
    // GET /api/v1/roles — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/roles')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_roles_for_any_role(): void
    {
        $user = $this->requester();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/roles — store
    // -------------------------------------------------------------------------

    public function test_store_creates_role_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/roles', [
                'name'        => 'test_role',
                'description' => 'A test role',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'test_role')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_403_for_requester_role(): void
    {
        $requester = $this->requester();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/roles', [
                'name' => 'hacker_role',
            ])
            ->assertStatus(403);
    }

    public function test_store_returns_422_when_name_is_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/roles', [
                'description' => 'Missing name',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    public function test_store_returns_422_when_name_is_duplicate(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/roles', [
                'name' => 'requester', // already seeded
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.');
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/roles/{role} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_role_for_any_authenticated_user(): void
    {
        $user = $this->requester();
        $role = Role::where('name', 'requester')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/roles/{$role->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $role->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_role(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/roles/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/roles/{role} — update
    // -------------------------------------------------------------------------

    public function test_update_succeeds_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $role = Role::where('name', 'requester')->firstOrFail();

        $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/roles/{$role->id}", [
                'description' => 'Updated description',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.description', 'Updated description');
    }

    public function test_update_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $role = Role::where('name', 'requester')->firstOrFail();

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/roles/{$role->id}", [
                'description' => 'Unauthorized update',
            ])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/roles/{role} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_role_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        // Create a new role to delete (not a seeded one, to avoid FK violations)
        $role = Role::create(['name' => 'temp_role', 'description' => 'Temporary']);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/roles/{$role->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);
    }

    public function test_destroy_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $role = Role::where('name', 'requester')->firstOrFail();

        $this->actingAs($requester, 'sanctum')
            ->deleteJson("/api/v1/roles/{$role->id}")
            ->assertStatus(403);
    }
}
