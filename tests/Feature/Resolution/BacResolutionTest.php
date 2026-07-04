<?php

declare(strict_types=1);

namespace Tests\Feature\Resolution;

use App\Models\AbstractOfQuotation;
use App\Models\BacResolution;
use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class BacResolutionTest extends TestCase
{
    private function procurementOfficer(): User
    {
        $role = Role::where('name', 'procurement_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function officeId(): int
    {
        return Office::where('code', 'ORM')->value('id');
    }

    private function createAbstractOfQuotation(User $user): AbstractOfQuotation
    {
        static $seq = 0;
        $seq++;

        $pr = PurchaseRequest::create([
            'requester_id' => $user->id,
            'requesting_office_id' => $this->officeId(),
            'purpose' => "Test PR #{$seq}",
            'status' => 'pr_approved',
        ]);

        $rfq = Rfq::create([
            'purchase_request_id' => $pr->id,
            'prepared_by_id' => $user->id,
            'rfq_number' => sprintf('RFQ-%d-%05d', now()->year, $seq),
            'status' => 'draft',
        ]);

        return AbstractOfQuotation::create([
            'rfq_id' => $rfq->id,
            'prepared_by_id' => $user->id,
            'status' => 'approved',
        ]);
    }

    private function createBacResolution(AbstractOfQuotation $abstract, User $user): BacResolution
    {
        static $seq = 0;
        $seq++;

        return BacResolution::create([
            'resolution_number' => "BAC-{$seq}",
            'abstract_of_quotation_id' => $abstract->id,
            'prepared_by_id' => $user->id,
            'file_path' => 'documents/bac/resolution.pdf',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/bac-resolutions — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/bac-resolutions')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_bac_resolutions(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/bac-resolutions')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_index_filters_by_abstract_of_quotation_id(): void
    {
        $officer = $this->procurementOfficer();
        $abstractA = $this->createAbstractOfQuotation($officer);
        $abstractB = $this->createAbstractOfQuotation($officer);
        $this->createBacResolution($abstractA, $officer);
        $this->createBacResolution($abstractB, $officer);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/bac-resolutions?abstract_of_quotation_id={$abstractA->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_search_resolution_number(): void
    {
        $officer = $this->procurementOfficer();
        $abstract = $this->createAbstractOfQuotation($officer);
        $this->createBacResolution($abstract, $officer);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/bac-resolutions?search=BAC');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/bac-resolutions — store
    // -------------------------------------------------------------------------

    public function test_store_creates_bac_resolution(): void
    {
        $officer = $this->procurementOfficer();
        $abstract = $this->createAbstractOfQuotation($officer);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/bac-resolutions', [
                'resolution_number' => 'BAC-2026-001',
                'abstract_of_quotation_id' => $abstract->id,
                'prepared_by_id' => $officer->id,
                'file_path' => 'documents/bac/res.pdf',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.resolution_number', 'BAC-2026-001')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/bac-resolutions', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['resolution_number', 'abstract_of_quotation_id', 'prepared_by_id', 'file_path']]);
    }

    public function test_store_returns_422_when_abstract_already_has_resolution(): void
    {
        $officer = $this->procurementOfficer();
        $abstract = $this->createAbstractOfQuotation($officer);
        $this->createBacResolution($abstract, $officer);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/bac-resolutions', [
                'resolution_number' => 'BAC-DUPLICATE',
                'abstract_of_quotation_id' => $abstract->id,
                'prepared_by_id' => $officer->id,
                'file_path' => 'documents/dup.pdf',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['abstract_of_quotation_id']]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/bac-resolutions/{bacResolution} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_bac_resolution(): void
    {
        $officer = $this->procurementOfficer();
        $abstract = $this->createAbstractOfQuotation($officer);
        $resolution = $this->createBacResolution($abstract, $officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/bac-resolutions/{$resolution->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $resolution->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_bac_resolution(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/bac-resolutions/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/bac-resolutions/{bacResolution} — update
    // -------------------------------------------------------------------------

    public function test_update_modifies_bac_resolution(): void
    {
        $officer = $this->procurementOfficer();
        $abstract = $this->createAbstractOfQuotation($officer);
        $resolution = $this->createBacResolution($abstract, $officer);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/bac-resolutions/{$resolution->id}", [
                'resolution_number' => 'BAC-UPDATED',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.resolution_number', 'BAC-UPDATED');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/bac-resolutions/{bacResolution} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_bac_resolution(): void
    {
        $officer = $this->procurementOfficer();
        $abstract = $this->createAbstractOfQuotation($officer);
        $resolution = $this->createBacResolution($abstract, $officer);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/bac-resolutions/{$resolution->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('bac_resolutions', ['id' => $resolution->id]);
    }
}
