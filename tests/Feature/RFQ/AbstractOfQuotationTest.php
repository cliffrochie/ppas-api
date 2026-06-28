<?php

declare(strict_types=1);

namespace Tests\Feature\RFQ;

use App\Models\AbstractOfQuotation;
use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AbstractOfQuotationTest extends TestCase
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

    private function createPurchaseRequest(User $user): PurchaseRequest
    {
        static $seq = 0;
        $seq++;

        return PurchaseRequest::create([
            'requester_id'         => $user->id,
            'requesting_office_id' => $this->officeId(),
            'purpose'              => "Test PR #{$seq}",
            'status'               => 'pr_approved',
        ]);
    }

    private function createRfq(PurchaseRequest $pr, User $user): Rfq
    {
        static $seq = 0;
        $seq++;

        return Rfq::create([
            'purchase_request_id' => $pr->id,
            'prepared_by_id'      => $user->id,
            'rfq_number'          => sprintf('RFQ-%d-%05d', now()->year, $seq),
            'status'              => 'draft',
        ]);
    }

    private function createAbstractOfQuotation(Rfq $rfq, User $user): AbstractOfQuotation
    {
        return AbstractOfQuotation::create([
            'rfq_id'         => $rfq->id,
            'prepared_by_id' => $user->id,
            'status'         => 'draft',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/abstracts-of-quotation — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/abstracts-of-quotation')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_abstracts(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/abstracts-of-quotation')
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
    // POST /api/v1/abstracts-of-quotation — store
    // -------------------------------------------------------------------------

    public function test_store_creates_abstract_of_quotation(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $rfq     = $this->createRfq($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/abstracts-of-quotation', [
                'rfq_id'         => $rfq->id,
                'prepared_by_id' => $officer->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/abstracts-of-quotation', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['rfq_id', 'prepared_by_id']]);
    }

    public function test_store_returns_422_when_rfq_already_has_abstract(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $rfq     = $this->createRfq($pr, $officer);
        $this->createAbstractOfQuotation($rfq, $officer);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/abstracts-of-quotation', [
                'rfq_id'         => $rfq->id,
                'prepared_by_id' => $officer->id,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['rfq_id']]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/abstracts-of-quotation/{abstractOfQuotation} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_abstract_of_quotation(): void
    {
        $officer  = $this->procurementOfficer();
        $pr       = $this->createPurchaseRequest($officer);
        $rfq      = $this->createRfq($pr, $officer);
        $abstract = $this->createAbstractOfQuotation($rfq, $officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/abstracts-of-quotation/{$abstract->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $abstract->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_abstract(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/abstracts-of-quotation/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/abstracts-of-quotation/{abstractOfQuotation} — update
    // -------------------------------------------------------------------------

    public function test_update_modifies_abstract_of_quotation(): void
    {
        $officer  = $this->procurementOfficer();
        $pr       = $this->createPurchaseRequest($officer);
        $rfq      = $this->createRfq($pr, $officer);
        $abstract = $this->createAbstractOfQuotation($rfq, $officer);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/abstracts-of-quotation/{$abstract->id}", [
                'recommended_supplier' => 'Best Supplier Corp.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.recommended_supplier', 'Best Supplier Corp.');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/abstracts-of-quotation/{abstractOfQuotation} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_abstract_of_quotation(): void
    {
        $officer  = $this->procurementOfficer();
        $pr       = $this->createPurchaseRequest($officer);
        $rfq      = $this->createRfq($pr, $officer);
        $abstract = $this->createAbstractOfQuotation($rfq, $officer);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/abstracts-of-quotation/{$abstract->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('abstracts_of_quotation', ['id' => $abstract->id]);
    }
}
