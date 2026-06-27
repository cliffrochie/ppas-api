<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\Office;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
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

    private function createPurchaseOrder(PurchaseRequest $pr, User $preparedBy): PurchaseOrder
    {
        return PurchaseOrder::create([
            'purchase_request_id' => $pr->id,
            'prepared_by_id'      => $preparedBy->id,
            'status'              => 'draft',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/purchase-orders — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/purchase-orders')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_purchase_orders(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-orders')
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
    // POST /api/v1/purchase-orders — store
    // -------------------------------------------------------------------------

    public function test_store_creates_purchase_order(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/purchase-orders', [
                'purchase_request_id' => $pr->id,
                'prepared_by_id'      => $officer->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/purchase-orders', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['purchase_request_id', 'prepared_by_id']]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/purchase-orders/{purchaseOrder} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_purchase_order(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $po      = $this->createPurchaseOrder($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/purchase-orders/{$po->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $po->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_purchase_order(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-orders/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/purchase-orders/{purchaseOrder} — update
    // -------------------------------------------------------------------------

    public function test_update_modifies_purchase_order(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $po      = $this->createPurchaseOrder($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/purchase-orders/{$po->id}", [
                'delivery_terms' => 'Net 30 days',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.delivery_terms', 'Net 30 days');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/purchase-orders/{purchaseOrder} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_purchase_order(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $po      = $this->createPurchaseOrder($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/purchase-orders/{$po->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('purchase_orders', ['id' => $po->id]);
    }

    // -------------------------------------------------------------------------
    // Auto-generated po_number
    // -------------------------------------------------------------------------

    public function test_po_number_is_auto_generated_on_create(): void
    {
        $officer = $this->procurementOfficer();
        $pr      = $this->createPurchaseRequest($officer);
        $year    = now()->year;

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/purchase-orders', [
                'purchase_request_id' => $pr->id,
                'prepared_by_id'      => $officer->id,
            ]);

        $response->assertStatus(201);
        $this->assertSame("PO-{$year}-00001", $response->json('data.po_number'));
    }

    public function test_po_number_increments_per_year(): void
    {
        $officer = $this->procurementOfficer();
        $pr1     = $this->createPurchaseRequest($officer);
        $pr2     = $this->createPurchaseRequest($officer);
        $year    = now()->year;

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/purchase-orders', [
                'purchase_request_id' => $pr1->id,
                'prepared_by_id'      => $officer->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.po_number', "PO-{$year}-00001");

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/purchase-orders', [
                'purchase_request_id' => $pr2->id,
                'prepared_by_id'      => $officer->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.po_number', "PO-{$year}-00002");
    }
}
