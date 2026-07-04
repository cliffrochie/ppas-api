<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Models\Office;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class OfficeTest extends TestCase
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
    // GET /api/v1/offices — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/offices')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_offices_for_any_role(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/offices')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_index_filters_by_search_matches_name(): void
    {
        $user = $this->requester();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/offices?search=Finance');

        $response->assertStatus(200);

        foreach ($response->json('data') as $item) {
            $nameOrCode = strtolower($item['name']).strtolower($item['code'] ?? '');
            $this->assertStringContainsString('finance', $nameOrCode);
        }
    }

    public function test_index_filters_by_search_matches_code(): void
    {
        $user = $this->requester();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/offices?search=BAC');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Bids and Awards Committee', $response->json('data.0.name'));
    }

    public function test_index_filters_by_search_returns_empty_when_no_match(): void
    {
        $user = $this->requester();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/offices?search=nonexistent_xyz_office');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/offices — store
    // -------------------------------------------------------------------------

    public function test_store_creates_office_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/offices', [
                'name' => 'Test Office',
                'code' => 'TO',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Office')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_403_for_requester_role(): void
    {
        $requester = $this->requester();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/offices', [
                'name' => 'Unauthorized Office',
                'code' => 'UO',
            ])
            ->assertStatus(403);
    }

    public function test_store_returns_422_when_name_is_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/offices', [
                'code' => 'NONAME',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/offices/{office} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_office_for_any_authenticated_user(): void
    {
        $user = $this->requester();
        $office = Office::where('code', 'ORM')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/offices/{$office->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $office->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_office(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/offices/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/offices/{office} — update
    // -------------------------------------------------------------------------

    public function test_update_succeeds_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $office = Office::where('code', 'ORM')->firstOrFail();

        $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/offices/{$office->id}", [
                'name' => 'Updated Office Name',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Office Name');
    }

    public function test_update_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $office = Office::where('code', 'ORM')->firstOrFail();

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/offices/{$office->id}", [
                'name' => 'Unauthorized Update',
            ])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/offices/{office} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_office_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $office = Office::create(['name' => 'Temp Office', 'code' => 'TMP']);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/offices/{$office->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);
    }

    public function test_destroy_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $office = Office::where('code', 'ORM')->firstOrFail();

        $this->actingAs($requester, 'sanctum')
            ->deleteJson("/api/v1/offices/{$office->id}")
            ->assertStatus(403);
    }
}
