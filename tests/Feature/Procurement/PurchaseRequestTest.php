<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class PurchaseRequestTest extends TestCase
{
    private function procurementOfficer(): User
    {
        $role = Role::where('name', 'procurement_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function requester(?int $officeId = null): User
    {
        $role = Role::where('name', 'requester')->firstOrFail();
        $office = $officeId ?? Office::where('code', 'ORM')->value('id');

        return User::factory()->create([
            'role_id' => $role->id,
            'office_id' => $office,
        ]);
    }

    private function budgetOfficer(): User
    {
        $role = Role::where('name', 'budget_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function bacSecretariat(): User
    {
        $role = Role::where('name', 'bac_secretariat')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function officeId(): int
    {
        return Office::where('code', 'ORM')->value('id');
    }

    private function createPurchaseRequest(User $requester, array $attributes = []): PurchaseRequest
    {
        return PurchaseRequest::create(array_merge([
            'requester_id' => $requester->id,
            'requesting_office_id' => $this->officeId(),
            'purpose' => 'Test procurement purpose',
            'status' => 'draft',
        ], $attributes));
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/purchase-requests — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/purchase-requests')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_purchase_requests(): void
    {
        $requester = $this->requester();

        $this->actingAs($requester, 'sanctum')
            ->getJson('/api/v1/purchase-requests')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_requester_cannot_see_other_users_prs(): void
    {
        $owner = $this->requester();
        $other = $this->requester();
        $this->createPurchaseRequest($owner, ['status' => 'submitted']);

        $response = $this->actingAs($other, 'sanctum')
            ->getJson('/api/v1/purchase-requests');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_requester_can_see_own_drafts(): void
    {
        $requester = $this->requester();
        $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $response = $this->actingAs($requester, 'sanctum')
            ->getJson('/api/v1/purchase-requests');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_officer_does_not_see_draft_prs(): void
    {
        $officer = $this->procurementOfficer();
        $requester = $this->requester();
        $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-requests');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_officer_sees_submitted_prs_from_all_requesters(): void
    {
        $officer = $this->procurementOfficer();
        $requesterA = $this->requester();
        $requesterB = $this->requester();
        $this->createPurchaseRequest($requesterA, ['status' => 'submitted']);
        $this->createPurchaseRequest($requesterB, ['status' => 'under_review']);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-requests');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_budget_officer_does_not_see_draft_prs(): void
    {
        $budgetOfficer = $this->budgetOfficer();
        $requester = $this->requester();
        $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $response = $this->actingAs($budgetOfficer, 'sanctum')
            ->getJson('/api/v1/purchase-requests');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_budget_officer_sees_submitted_prs(): void
    {
        $budgetOfficer = $this->budgetOfficer();
        $requester = $this->requester();
        $this->createPurchaseRequest($requester, ['status' => 'for_budget_approval']);

        $response = $this->actingAs($budgetOfficer, 'sanctum')
            ->getJson('/api/v1/purchase-requests');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_status_for_officer(): void
    {
        $officer = $this->procurementOfficer();
        $requester = $this->requester();
        $this->createPurchaseRequest($requester, ['status' => 'submitted']);
        $this->createPurchaseRequest($requester, ['status' => 'under_review']);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-requests?status=submitted');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('submitted', $response->json('data.0.status'));
    }

    public function test_index_filters_by_search_on_purpose_for_officer(): void
    {
        $officer = $this->procurementOfficer();
        $requester = $this->requester();
        $this->createPurchaseRequest($requester, ['status' => 'submitted', 'purpose' => 'Unique keyboard procurement']);
        $this->createPurchaseRequest($requester, ['status' => 'submitted', 'purpose' => 'Office chairs purchase']);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-requests?search=keyboard');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsStringIgnoringCase('keyboard', $response->json('data.0.purpose'));
    }

    public function test_index_filters_by_requesting_office_id_for_officer(): void
    {
        $officer = $this->procurementOfficer();
        $requester = $this->requester();
        $orm = Office::where('code', 'ORM')->firstOrFail();
        $bac = Office::where('code', 'BAC')->firstOrFail();

        $this->createPurchaseRequest($requester, ['status' => 'submitted', 'requesting_office_id' => $orm->id]);
        $this->createPurchaseRequest($requester, ['status' => 'submitted', 'requesting_office_id' => $bac->id]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/purchase-requests?requesting_office_id={$orm->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/purchase-requests — store
    // -------------------------------------------------------------------------

    public function test_store_creates_purchase_request_as_requester(): void
    {
        $requester = $this->requester();
        $officeId = $this->officeId();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/purchase-requests', [
                'requester_id' => $requester->id,
                'requesting_office_id' => $officeId,
                'purpose' => 'Purchase office supplies',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.purpose', 'Purchase office supplies')
            ->assertJsonPath('errors', null);
    }

    public function test_store_creates_purchase_request_as_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $requester = $this->requester();
        $officeId = $this->officeId();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/purchase-requests', [
                'requester_id' => $requester->id,
                'requesting_office_id' => $officeId,
                'purpose' => 'Procurement officer submission',
            ])
            ->assertStatus(201)
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $requester = $this->requester();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/purchase-requests', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['requester_id', 'requesting_office_id', 'purpose']]);
    }

    public function test_store_never_accepts_pr_number_from_input(): void
    {
        $requester = $this->requester();
        $officeId = $this->officeId();

        $response = $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/purchase-requests', [
                'requester_id' => $requester->id,
                'requesting_office_id' => $officeId,
                'purpose' => 'Test',
                'pr_number' => 'HACKED-PR-001', // must be ignored
            ]);

        $response->assertStatus(201);
        // pr_number is system-generated; user-supplied value must not be set
        $this->assertNull(PurchaseRequest::latest()->first()->pr_number);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/purchase-requests/{purchase_request} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_purchase_request_to_owner(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester);

        $this->actingAs($requester, 'sanctum')
            ->getJson("/api/v1/purchase-requests/{$pr->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $pr->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_403_when_requester_views_another_users_pr(): void
    {
        $owner = $this->requester();
        $other = $this->requester();
        $pr = $this->createPurchaseRequest($owner);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/purchase-requests/{$pr->id}")
            ->assertStatus(403);
    }

    public function test_show_returns_404_for_missing_purchase_request(): void
    {
        $user = $this->requester();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/purchase-requests/999999')
            ->assertStatus(404);
    }

    public function test_show_allows_procurement_officer_to_view_any_pr(): void
    {
        $officer = $this->procurementOfficer();
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/purchase-requests/{$pr->id}")
            ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // PUT /api/v1/purchase-requests/{purchase_request} — update
    // -------------------------------------------------------------------------

    public function test_update_succeeds_for_owner_on_draft(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'purpose' => 'Updated purpose',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.purpose', 'Updated purpose');
    }

    public function test_update_returns_403_when_requester_updates_submitted_pr(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'submitted']);

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'purpose' => 'Attempted change after submit',
            ])
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/purchase-requests/{purchase_request} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_succeeds_for_owner_on_draft_pr(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $this->actingAs($requester, 'sanctum')
            ->deleteJson("/api/v1/purchase-requests/{$pr->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);
    }

    public function test_destroy_returns_403_for_non_owner_requester(): void
    {
        $owner = $this->requester();
        $other = $this->requester();
        $pr = $this->createPurchaseRequest($owner, ['status' => 'draft']);

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/v1/purchase-requests/{$pr->id}")
            ->assertStatus(403);
    }

    public function test_destroy_succeeds_for_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'completed']);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/purchase-requests/{$pr->id}")
            ->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Auto-generated fields
    // -------------------------------------------------------------------------

    public function test_rf_number_is_null_on_store(): void
    {
        // rf_number must NOT be generated at draft creation — only at draft → submitted.
        $requester = $this->requester();
        $officeId = $this->officeId();

        $response = $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/purchase-requests', [
                'requester_id' => $requester->id,
                'requesting_office_id' => $officeId,
                'purpose' => 'Test RF number is null on store',
            ]);

        $response->assertStatus(201);
        $this->assertNull($response->json('data.rf_number'));
    }

    public function test_rf_number_is_auto_generated_on_submit(): void
    {
        // rf_number is generated when the Requester transitions draft → submitted.
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'draft']);
        $year = now()->year;
        $month = now()->format('m');

        $this->assertNull($pr->rf_number);

        $response = $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'submitted',
            ]);

        $response->assertStatus(200);
        $this->assertSame("RF-{$year}-{$month}-001", $response->json('data.rf_number'));
    }

    public function test_rf_number_is_preserved_on_resubmission(): void
    {
        // When a returned request is resubmitted, the original RF # must be kept.
        $requester = $this->requester();
        $year = now()->year;

        // rf_number is not in $fillable so it cannot be set via create().
        // Use forceFill after creation to seed the value directly.
        $pr = $this->createPurchaseRequest($requester, ['status' => 'returned']);
        $pr->forceFill(['rf_number' => "RF-{$year}-00042"])->save();

        $response = $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'submitted',
            ]);

        $response->assertStatus(200);
        $this->assertSame("RF-{$year}-00042", $response->json('data.rf_number'));
    }

    public function test_pr_number_is_null_on_submission(): void
    {
        // pr_number must NOT be generated at draft → submitted — only at forwarded_to_ppu → pr_prepared.
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $response = $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'submitted',
            ]);

        $response->assertStatus(200);
        $this->assertNull($response->json('data.pr_number'));
    }

    public function test_pr_number_is_auto_generated_on_pr_prepared(): void
    {
        // pr_number is generated when PPU transitions forwarded_to_ppu → pr_prepared.
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer, ['status' => 'forwarded_to_ppu']);
        $year = now()->year;
        $month = now()->format('m');

        $this->assertNull($pr->pr_number);

        $response = $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'pr_prepared',
            ]);

        $response->assertStatus(200);
        $this->assertSame("PR-{$year}-{$month}-001", $response->json('data.pr_number'));
    }

    // -------------------------------------------------------------------------
    // Status transition guard
    // -------------------------------------------------------------------------

    public function test_invalid_status_transition_returns_422(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer, ['status' => 'draft']);

        // draft → completed is not a valid transition.
        $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'completed',
            ])
            ->assertStatus(422)
            ->assertJsonPath('data', null)
            ->assertJsonPath('errors', null)
            ->assertJsonFragment(['message' => "Invalid status transition from 'draft' to 'completed'."]);
    }

    public function test_valid_status_transition_succeeds(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'draft']);

        // draft → submitted is the first valid transition for a requester.
        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'submitted',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');
    }

    public function test_wrong_role_cannot_transition_status(): void
    {
        // The requester owns the PR and it is in 'submitted' status.
        // The requester cannot move submitted → under_review — only bac_secretariat can.
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'submitted']);

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'under_review',
            ])
            ->assertStatus(403);
    }

    public function test_bac_secretariat_can_move_submitted_to_under_review(): void
    {
        $bacSecretariat = $this->bacSecretariat();
        $pr = $this->createPurchaseRequest($bacSecretariat, ['status' => 'submitted']);

        $this->actingAs($bacSecretariat, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'under_review',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'under_review');
    }

    public function test_bac_secretariat_can_return_a_submitted_pr(): void
    {
        $bacSecretariat = $this->bacSecretariat();
        $pr = $this->createPurchaseRequest($bacSecretariat, ['status' => 'submitted']);

        $this->actingAs($bacSecretariat, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'returned',
                'remarks' => 'Missing attachment.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'returned');
    }

    public function test_bac_secretariat_can_move_under_review_to_for_budget_approval(): void
    {
        $bacSecretariat = $this->bacSecretariat();
        $pr = $this->createPurchaseRequest($bacSecretariat, ['status' => 'under_review']);

        $this->actingAs($bacSecretariat, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'for_budget_approval',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'for_budget_approval');
    }

    public function test_bac_secretariat_cannot_perform_ppu_only_transition(): void
    {
        // forwarded_to_ppu → pr_prepared is PPU's job, not BAC Secretariat's.
        $bacSecretariat = $this->bacSecretariat();
        $pr = $this->createPurchaseRequest($bacSecretariat, ['status' => 'forwarded_to_ppu']);

        $this->actingAs($bacSecretariat, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'pr_prepared',
            ])
            ->assertStatus(403);
    }

    public function test_procurement_officer_can_no_longer_move_submitted_to_under_review(): void
    {
        // This capability moved to bac_secretariat when the roles were split.
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer, ['status' => 'submitted']);

        $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'under_review',
            ])
            ->assertStatus(403);
    }

    public function test_procurement_officer_can_no_longer_return_a_submitted_pr(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer, ['status' => 'submitted']);

        $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'returned',
                'remarks' => 'Missing attachment.',
            ])
            ->assertStatus(403);
    }

    public function test_procurement_officer_can_no_longer_move_under_review_to_for_budget_approval(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer, ['status' => 'under_review']);

        $this->actingAs($officer, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'for_budget_approval',
            ])
            ->assertStatus(403);
    }

    public function test_submitted_at_is_set_on_submit(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $this->assertNull($pr->submitted_at);

        $response = $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'submitted',
            ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.submitted_at'));
    }

    // -------------------------------------------------------------------------
    // Status history (C1 + C3)
    // -------------------------------------------------------------------------

    public function test_status_history_row_written_on_transition(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'submitted',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('pr_status_histories', [
            'purchase_request_id' => $pr->id,
            'actor_id' => $requester->id,
            'from_status' => 'draft',
            'to_status' => 'submitted',
        ]);
    }

    public function test_alobs_number_captured_in_status_history_on_budget_approval(): void
    {
        $budgetOfficer = $this->budgetOfficer();
        $pr = $this->createPurchaseRequest($budgetOfficer, ['status' => 'for_budget_approval']);

        $this->actingAs($budgetOfficer, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'budget_approved',
                'alobs_number' => 'ALOBS-2026-001',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('pr_status_histories', [
            'purchase_request_id' => $pr->id,
            'from_status' => 'for_budget_approval',
            'to_status' => 'budget_approved',
            'alobs_number' => 'ALOBS-2026-001',
        ]);
    }

    public function test_remarks_captured_in_status_history_on_return(): void
    {
        $bacSecretariat = $this->bacSecretariat();
        $pr = $this->createPurchaseRequest($bacSecretariat, ['status' => 'submitted']);

        $this->actingAs($bacSecretariat, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'returned',
                'remarks' => 'Missing PPMP attachment.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('pr_status_histories', [
            'purchase_request_id' => $pr->id,
            'from_status' => 'submitted',
            'to_status' => 'returned',
            'remarks' => 'Missing PPMP attachment.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Notifications (C2)
    // -------------------------------------------------------------------------

    public function test_notification_created_for_bac_secretariat_on_submission(): void
    {
        $bacSecretariat = $this->bacSecretariat();
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'submitted',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $bacSecretariat->id,
            'purchase_request_id' => $pr->id,
            'type' => 'pr_submitted',
        ]);
    }

    public function test_notification_created_for_requester_on_return(): void
    {
        $bacSecretariat = $this->bacSecretariat();
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'submitted']);

        $this->actingAs($bacSecretariat, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'returned',
                'remarks' => 'Incomplete submission.',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $requester->id,
            'purchase_request_id' => $pr->id,
            'type' => 'pr_returned',
        ]);
    }

    public function test_actor_does_not_receive_own_notification(): void
    {
        // When the requester submits, they should NOT receive a notification
        // directed at the requester role (they triggered the action themselves).
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, [
            'status' => 'budget_approved',
            'rf_number' => 'RF-2026-00001',
        ]);

        // Transition budget_approved → forwarded_to_ppu (budget_officer role).
        // The actor here is the budget officer; procurement officers get notified.
        $budgetOfficer = $this->budgetOfficer();

        $this->actingAs($budgetOfficer, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'forwarded_to_ppu',
            ])
            ->assertStatus(200);

        // budget_officer must NOT appear in notifications for this transition
        // (they triggered it; the notification goes to procurement_officers).
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $budgetOfficer->id,
            'purchase_request_id' => $pr->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Audit logging
    // -------------------------------------------------------------------------

    public function test_audit_log_created_on_store(): void
    {
        $requester = $this->requester();
        $officeId = $this->officeId();

        $this->actingAs($requester, 'sanctum')
            ->postJson('/api/v1/purchase-requests', [
                'requester_id' => $requester->id,
                'requesting_office_id' => $officeId,
                'purpose' => 'Audit creation test',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => PurchaseRequest::class,
            'event' => 'created',
        ]);
    }

    public function test_audit_log_written_on_field_change(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['purpose' => 'Original purpose']);

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'purpose' => 'Updated purpose',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => PurchaseRequest::class,
            'auditable_id' => $pr->id,
            'event' => 'updated',
            'field' => 'purpose',
            'old_value' => 'Original purpose',
            'new_value' => 'Updated purpose',
        ]);
    }

    public function test_status_change_audit_uses_status_changed_event(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester, ['status' => 'draft']);

        $this->actingAs($requester, 'sanctum')
            ->putJson("/api/v1/purchase-requests/{$pr->id}", [
                'status' => 'submitted',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => PurchaseRequest::class,
            'auditable_id' => $pr->id,
            'event' => 'status_changed',
            'field' => 'status',
            'old_value' => 'draft',
            'new_value' => 'submitted',
        ]);
    }

    public function test_audit_log_written_on_destroy(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $prId = $pr->id;

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/purchase-requests/{$prId}")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => PurchaseRequest::class,
            'auditable_id' => $prId,
            'event' => 'deleted',
            'field' => 'id',
            'old_value' => (string) $prId,
            'new_value' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Response envelope integrity
    // -------------------------------------------------------------------------

    public function test_pr_resource_never_exposes_internal_file_paths(): void
    {
        $requester = $this->requester();
        $pr = $this->createPurchaseRequest($requester);

        $response = $this->actingAs($requester, 'sanctum')
            ->getJson("/api/v1/purchase-requests/{$pr->id}");

        $data = $response->json('data');
        // file_path must never be returned from PR resource
        $this->assertArrayNotHasKey('file_path', $data);
    }
}
