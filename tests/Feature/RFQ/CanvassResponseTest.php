<?php

declare(strict_types=1);

namespace Tests\Feature\RFQ;

use App\Models\CanvassResponse;
use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class CanvassResponseTest extends TestCase
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

    private function createRfqItem(Rfq $rfq, PurchaseRequest $pr): RfqItem
    {
        $prItem = PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'item_description'    => 'Bond Paper A4',
            'unit_of_measure'     => 'ream',
            'quantity'            => 10,
            'unit_cost'           => 200.00,
            'total_cost'          => 2000.00,
        ]);

        return RfqItem::create([
            'rfq_id'           => $rfq->id,
            'pr_item_id'       => $prItem->id,
            'item_description' => 'Bond Paper A4',
            'unit_of_measure'  => 'ream',
            'quantity'         => 10,
        ]);
    }

    private function createCanvassResponse(Rfq $rfq, RfqItem $item): CanvassResponse
    {
        return CanvassResponse::create([
            'rfq_id'        => $rfq->id,
            'rfq_item_id'   => $item->id,
            'supplier_name' => 'ABC Supplies',
            'unit_price'    => 180.00,
            'total_price'   => 1800.00,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/canvass-responses — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/canvass-responses')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_canvass_responses(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/canvass-responses')
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
    // POST /api/v1/canvass-responses — store
    // -------------------------------------------------------------------------

    public function test_store_creates_canvass_response(): void
    {
        $officer  = $this->procurementOfficer();
        $pr       = $this->createPurchaseRequest($officer);
        $rfq      = $this->createRfq($pr, $officer);
        $rfqItem  = $this->createRfqItem($rfq, $pr);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/canvass-responses', [
                'rfq_id'        => $rfq->id,
                'rfq_item_id'   => $rfqItem->id,
                'supplier_name' => 'DEF Supplies',
                'unit_price'    => 190.00,
                'total_price'   => 1900.00,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.supplier_name', 'DEF Supplies')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/canvass-responses', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['rfq_id', 'rfq_item_id', 'supplier_name', 'unit_price', 'total_price']]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/canvass-responses/{canvassResponse} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_canvass_response(): void
    {
        $officer  = $this->procurementOfficer();
        $pr       = $this->createPurchaseRequest($officer);
        $rfq      = $this->createRfq($pr, $officer);
        $rfqItem  = $this->createRfqItem($rfq, $pr);
        $response = $this->createCanvassResponse($rfq, $rfqItem);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/canvass-responses/{$response->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $response->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_canvass_response(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/canvass-responses/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/canvass-responses/{canvassResponse} — update
    // -------------------------------------------------------------------------

    public function test_update_modifies_canvass_response(): void
    {
        $officer  = $this->procurementOfficer();
        $pr       = $this->createPurchaseRequest($officer);
        $rfq      = $this->createRfq($pr, $officer);
        $rfqItem  = $this->createRfqItem($rfq, $pr);
        $response = $this->createCanvassResponse($rfq, $rfqItem);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/canvass-responses/{$response->id}", [
                'supplier_name' => 'Updated Supplier',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.supplier_name', 'Updated Supplier');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/canvass-responses/{canvassResponse} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_canvass_response(): void
    {
        $officer  = $this->procurementOfficer();
        $pr       = $this->createPurchaseRequest($officer);
        $rfq      = $this->createRfq($pr, $officer);
        $rfqItem  = $this->createRfqItem($rfq, $pr);
        $response = $this->createCanvassResponse($rfq, $rfqItem);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/canvass-responses/{$response->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('canvass_responses', ['id' => $response->id]);
    }
}
