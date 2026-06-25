<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    private function createCategory(string $name = 'Test Category'): Category
    {
        return Category::create([
            'name'        => $name,
            'description' => null,
            'is_active'   => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/categories — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/categories')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_categories_for_any_role(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/categories — store
    // -------------------------------------------------------------------------

    public function test_store_creates_category_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/categories', [
                'name'      => 'Office Supplies',
                'is_active' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Office Supplies')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_403_for_requester_role(): void
    {
        $requester = $this->requester();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/categories', [
                'name' => 'Blocked Category',
            ])
            ->assertStatus(403);
    }

    public function test_store_returns_422_when_name_is_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/categories', [
                'description' => 'No name provided',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/categories/{category} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_category_for_any_authenticated_user(): void
    {
        $user = $this->requester();
        $category = $this->createCategory('Visible Category');

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_category(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/categories/{category} — update
    // -------------------------------------------------------------------------

    public function test_update_succeeds_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $category = $this->createCategory('Original Name');

        $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", [
                'name' => 'Updated Name',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_update_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $category = $this->createCategory('Protected Category');

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", [
                'name' => 'Unauthorized Update',
            ])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/categories/{category} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_category_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $category = $this->createCategory('To Delete');

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);
    }

    public function test_destroy_returns_403_for_requester(): void
    {
        $requester = $this->requester();
        $category = $this->createCategory('Protected');

        $this->actingAs($requester, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(403);
    }
}
