<?php

declare(strict_types=1);

namespace Tests\Feature\RFQ;

use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RfqItemTest extends TestCase
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

    private function createPrItem(PurchaseRequest $pr): PurchaseRequestItem
    {
        return PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'item_description'    => 'Bond Paper A4',
            'unit_of_measure'     => 'ream',
            'quantity'            => 10,
            'unit_cost'           => 200.00,
            'total_cost'          => 2000.00,
        ]);
    }

    private function createRfqItem(Rfq $rfq, PurchaseRequestItem $prItem): RfqItem
    {
        return RfqItem::create([
            'rfq_id'           => $rfq->id,
            'pr_item_id'       => $prItem->id,
            'item_description' => 'Bond Paper A4',
            'unit_of_measure'  => 'ream',
            'quantity'         => 10,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/rfq-items — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/rfq-items')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_rfq_items(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/rfq-items')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_index_filters_by_search_matches_item_description(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $rfq     = $this->createRfq($pr, $officer);
        $prItem  = $this->createPrItem($pr);

        RfqItem::create(['rfq_id' => $rfq->id, 'pr_item_id' => $prItem->id, 'item_description' => 'Bond Paper A4', 'unit_of_measure' => 'ream', 'quantity' => 10]);
        RfqItem::create(['rfq_id' => $rfq->id, 'pr_item_id' => $prItem->id, 'item_description' => 'Ballpoint Pens', 'unit_of_measure' => 'box', 'quantity' => 4]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/rfq-items?search=bond');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsStringIgnoringCase('bond', $response->json('data.0.item_description'));
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/rfq-items — store
    // -------------------------------------------------------------------------

    public function test_store_creates_rfq_item(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $rfq     = $this->createRfq($pr, $officer);
        $prItem  = $this->createPrItem($pr);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/rfq-items', [
                'rfq_id'           => $rfq->id,
                'pr_item_id'       => $prItem->id,
                'item_description' => 'Bond Paper A4',
                'unit_of_measure'  => 'ream',
                'quantity'         => 5,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.item_description', 'Bond Paper A4')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/rfq-items', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['rfq_id', 'pr_item_id', 'item_description', 'unit_of_measure', 'quantity']]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/rfq-items/{rfqItem} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_rfq_item(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $rfq     = $this->createRfq($pr, $officer);
        $prItem  = $this->createPrItem($pr);
        $item    = $this->createRfqItem($rfq, $prItem);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/rfq-items/{$item->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_item(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/rfq-items/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/rfq-items/{rfqItem} — update
    // -------------------------------------------------------------------------

    public function test_update_modifies_rfq_item(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $rfq     = $this->createRfq($pr, $officer);
        $prItem  = $this->createPrItem($pr);
        $item    = $this->createRfqItem($rfq, $prItem);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/rfq-items/{$item->id}", [
                'item_description' => 'Updated Item',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.item_description', 'Updated Item');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/rfq-items/{rfqItem} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_rfq_item(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $rfq     = $this->createRfq($pr, $officer);
        $prItem  = $this->createPrItem($pr);
        $item    = $this->createRfqItem($rfq, $prItem);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/rfq-items/{$item->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('rfq_items', ['id' => $item->id]);
    }
}
