<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\Office;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class PurchaseOrderItemTest extends TestCase
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
        return PurchaseRequest::create([
            'requester_id'         => $user->id,
            'requesting_office_id' => $this->officeId(),
            'purpose'              => 'Test procurement purpose',
            'status'               => 'pr_approved',
        ]);
    }

    private function createPurchaseOrder(PurchaseRequest $pr, User $user): PurchaseOrder
    {
        static $seq = 0;
        $seq++;

        return PurchaseOrder::create([
            'purchase_request_id' => $pr->id,
            'prepared_by_id'      => $user->id,
            'po_number'           => sprintf('PO-%d-%05d', now()->year, $seq),
            'status'              => 'draft',
        ]);
    }

    private function createPurchaseOrderItem(PurchaseOrder $po): PurchaseOrderItem
    {
        return PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'item_description'  => 'Bond Paper A4',
            'unit_of_measure'   => 'ream',
            'quantity'          => 5,
            'unit_cost'         => 200.00,
            'total_cost'        => 1000.00,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/purchase-order-items — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/purchase-order-items')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_purchase_order_items(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-order-items')
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
        $po      = $this->createPurchaseOrder($pr, $officer);

        PurchaseOrderItem::create(['purchase_order_id' => $po->id, 'item_description' => 'Bond Paper A4', 'unit_of_measure' => 'ream', 'quantity' => 5, 'unit_cost' => 200.00, 'total_cost' => 1000.00]);
        PurchaseOrderItem::create(['purchase_order_id' => $po->id, 'item_description' => 'Ballpoint Pens', 'unit_of_measure' => 'box', 'quantity' => 3, 'unit_cost' => 150.00, 'total_cost' => 450.00]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-order-items?search=bond');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsStringIgnoringCase('bond', $response->json('data.0.item_description'));
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/purchase-order-items — store
    // -------------------------------------------------------------------------

    public function test_store_creates_purchase_order_item(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $po      = $this->createPurchaseOrder($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/purchase-order-items', [
                'purchase_order_id' => $po->id,
                'item_description'  => 'Bond Paper A4',
                'unit_of_measure'   => 'ream',
                'quantity'          => 10,
                'unit_cost'         => 250.00,
                'total_cost'        => 2500.00,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.item_description', 'Bond Paper A4')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/purchase-order-items', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['purchase_order_id', 'item_description', 'unit_of_measure', 'quantity', 'unit_cost', 'total_cost']]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/purchase-order-items/{purchaseOrderItem} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_purchase_order_item(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $po      = $this->createPurchaseOrder($pr, $officer);
        $item    = $this->createPurchaseOrderItem($po);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/purchase-order-items/{$item->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_item(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-order-items/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/purchase-order-items/{purchaseOrderItem} — update
    // -------------------------------------------------------------------------

    public function test_update_modifies_purchase_order_item(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $po      = $this->createPurchaseOrder($pr, $officer);
        $item    = $this->createPurchaseOrderItem($po);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/purchase-order-items/{$item->id}", [
                'item_description' => 'Updated Description',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.item_description', 'Updated Description');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/purchase-order-items/{purchaseOrderItem} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_purchase_order_item(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $po      = $this->createPurchaseOrder($pr, $officer);
        $item    = $this->createPurchaseOrderItem($po);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/purchase-order-items/{$item->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('purchase_order_items', ['id' => $item->id]);
    }
}
