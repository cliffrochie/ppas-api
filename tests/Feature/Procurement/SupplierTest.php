<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class SupplierTest extends TestCase
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

    private function createSupplier(string $email = 'supplier@test.com'): Supplier
    {
        return Supplier::create([
            'name' => 'Test Supplier Corp.',
            'email' => $email,
            'is_active' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/suppliers — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/suppliers')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_suppliers_for_any_role(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/suppliers')
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
        Supplier::create(['name' => 'UniqueSupplier Trading', 'email' => 'unique@trading.com', 'is_active' => true]);
        Supplier::create(['name' => 'Another Corp', 'email' => 'another@corp.com', 'is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/suppliers?search=UniqueSupplier');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('UniqueSupplier Trading', $response->json('data.0.name'));
    }

    public function test_index_filters_by_is_active(): void
    {
        $user = $this->requester();
        Supplier::create(['name' => 'Active One', 'email' => 'active@one.com', 'is_active' => true]);
        Supplier::create(['name' => 'Inactive One', 'email' => 'inactive@one.com', 'is_active' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/suppliers?is_active=0');

        $response->assertStatus(200);

        foreach ($response->json('data') as $item) {
            $this->assertFalse($item['is_active']);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/suppliers — store (procurement_officer only)
    // -------------------------------------------------------------------------

    public function test_store_creates_supplier_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/suppliers', [
                'name' => 'ABC Trading',
                'email' => 'abc@trading.com',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'ABC Trading')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_403_for_requester(): void
    {
        $requester = $this->requester();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/suppliers', [
                'name' => 'Blocked Supplier',
                'email' => 'blocked@test.com',
            ])
            ->assertStatus(403);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/suppliers', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['name', 'email']]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/suppliers/{supplier} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_supplier_for_any_authenticated_user(): void
    {
        $user = $this->requester();
        $supplier = $this->createSupplier('show@test.com');

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/suppliers/{$supplier->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $supplier->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_supplier(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/suppliers/999999')
            ->assertStatus(404);
    }

    public function test_logo_path_not_exposed_in_response(): void
    {
        // logo_path is a private disk path — must never appear in API output.
        // Consumers receive logo_url (an authorized download URL) instead.
        $user = $this->requester();
        $supplier = $this->createSupplier('logotest@test.com');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('logo_path', $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/suppliers/{supplier} — update
    // -------------------------------------------------------------------------

    public function test_update_succeeds_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $supplier = $this->createSupplier('update@test.com');

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/suppliers/{$supplier->id}", [
                'name' => 'Updated Supplier Name',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Supplier Name');
    }

    public function test_update_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $supplier = $this->createSupplier('protected@test.com');

        $this->actingAs($requester, 'sanctum')
            ->patchJson("/api/v1/suppliers/{$supplier->id}", [
                'name' => 'Unauthorized Update',
            ])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/suppliers/{supplier} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_supplier_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $supplier = $this->createSupplier('delete@test.com');

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/suppliers/{$supplier->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_destroy_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $supplier = $this->createSupplier('protected2@test.com');

        $this->actingAs($requester, 'sanctum')
            ->deleteJson("/api/v1/suppliers/{$supplier->id}")
            ->assertStatus(403);
    }
}
