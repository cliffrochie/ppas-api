<?php

declare(strict_types=1);

namespace Tests\Feature\Monitoring;

use App\Models\Office;
use App\Models\PrStatusHistory;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class PrStatusHistoryTest extends TestCase
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

    private function createStatusHistory(User $user): PrStatusHistory
    {
        $pr = PurchaseRequest::create([
            'requester_id' => $user->id,
            'requesting_office_id' => $this->officeId(),
            'purpose' => 'Test PR',
            'status' => 'submitted',
        ]);

        return PrStatusHistory::create([
            'purchase_request_id' => $pr->id,
            'actor_id' => $user->id,
            'from_status' => 'draft',
            'to_status' => 'submitted',
            'acted_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/pr-status-histories — index (read-only)
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/pr-status-histories')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_status_histories(): void
    {
        $officer = $this->procurementOfficer();
        $this->createStatusHistory($officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/pr-status-histories')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_index_filters_by_to_status(): void
    {
        $officer = $this->procurementOfficer();
        $pr = PurchaseRequest::create([
            'requester_id' => $officer->id,
            'requesting_office_id' => $this->officeId(),
            'purpose' => 'Test PR',
            'status' => 'under_review',
        ]);

        PrStatusHistory::create(['purchase_request_id' => $pr->id, 'actor_id' => $officer->id, 'to_status' => 'submitted', 'acted_at' => now()]);
        PrStatusHistory::create(['purchase_request_id' => $pr->id, 'actor_id' => $officer->id, 'to_status' => 'under_review', 'acted_at' => now()]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/pr-status-histories?to_status=submitted');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('submitted', $response->json('data.0.to_status'));
    }

    public function test_index_filters_by_search_matches_remarks(): void
    {
        $officer = $this->procurementOfficer();
        $pr = PurchaseRequest::create([
            'requester_id' => $officer->id,
            'requesting_office_id' => $this->officeId(),
            'purpose' => 'Test PR',
            'status' => 'under_review',
        ]);

        PrStatusHistory::create(['purchase_request_id' => $pr->id, 'actor_id' => $officer->id, 'to_status' => 'submitted', 'remarks' => 'Endorsed to budget office', 'acted_at' => now()]);
        PrStatusHistory::create(['purchase_request_id' => $pr->id, 'actor_id' => $officer->id, 'to_status' => 'under_review', 'remarks' => 'Returned for correction', 'acted_at' => now()]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/pr-status-histories?search=endorsed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsStringIgnoringCase('endorsed', $response->json('data.0.remarks'));
    }

    public function test_index_filters_by_purchase_request_id(): void
    {
        $officer = $this->procurementOfficer();
        $prA = PurchaseRequest::create(['requester_id' => $officer->id, 'requesting_office_id' => $this->officeId(), 'purpose' => 'PR A', 'status' => 'submitted']);
        $prB = PurchaseRequest::create(['requester_id' => $officer->id, 'requesting_office_id' => $this->officeId(), 'purpose' => 'PR B', 'status' => 'submitted']);

        PrStatusHistory::create(['purchase_request_id' => $prA->id, 'actor_id' => $officer->id, 'to_status' => 'submitted', 'acted_at' => now()]);
        PrStatusHistory::create(['purchase_request_id' => $prB->id, 'actor_id' => $officer->id, 'to_status' => 'submitted', 'acted_at' => now()]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/pr-status-histories?purchase_request_id={$prA->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/pr-status-histories/{prStatusHistory} — show (read-only)
    // -------------------------------------------------------------------------

    public function test_show_returns_status_history(): void
    {
        $officer = $this->procurementOfficer();
        $history = $this->createStatusHistory($officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/pr-status-histories/{$history->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $history->id)
            ->assertJsonPath('data.from_status', 'draft')
            ->assertJsonPath('data.to_status', 'submitted')
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_history(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/pr-status-histories/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Write methods are not registered (read-only resource)
    // -------------------------------------------------------------------------

    public function test_post_to_index_is_not_found(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/pr-status-histories', [])
            ->assertStatus(405);
    }

    public function test_delete_is_not_found(): void
    {
        $officer = $this->procurementOfficer();
        $history = $this->createStatusHistory($officer);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/pr-status-histories/{$history->id}")
            ->assertStatus(405);
    }
}
