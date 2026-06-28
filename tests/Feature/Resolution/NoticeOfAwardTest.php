<?php

declare(strict_types=1);

namespace Tests\Feature\Resolution;

use App\Models\AbstractOfQuotation;
use App\Models\BacResolution;
use App\Models\NoticeOfAward;
use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class NoticeOfAwardTest extends TestCase
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

    private function createBacResolution(User $user): BacResolution
    {
        static $seq = 0;
        $seq++;

        $pr = PurchaseRequest::create([
            'requester_id'         => $user->id,
            'requesting_office_id' => $this->officeId(),
            'purpose'              => "Test PR #{$seq}",
            'status'               => 'pr_approved',
        ]);

        $rfq = Rfq::create([
            'purchase_request_id' => $pr->id,
            'prepared_by_id'      => $user->id,
            'rfq_number'          => sprintf('RFQ-%d-%05d', now()->year, $seq),
            'status'              => 'draft',
        ]);

        $abstract = AbstractOfQuotation::create([
            'rfq_id'         => $rfq->id,
            'prepared_by_id' => $user->id,
            'status'         => 'approved',
        ]);

        return BacResolution::create([
            'resolution_number'        => "BAC-{$seq}",
            'abstract_of_quotation_id' => $abstract->id,
            'prepared_by_id'           => $user->id,
            'file_path'                => 'documents/bac/resolution.pdf',
        ]);
    }

    private function createNoticeOfAward(BacResolution $resolution): NoticeOfAward
    {
        static $seq = 0;
        $seq++;

        return NoticeOfAward::create([
            'noa_number'        => "NOA-{$seq}",
            'bac_resolution_id' => $resolution->id,
            'awarded_supplier'  => 'Best Supplier Corp.',
            'awarded_amount'    => 50000.00,
            'file_path'         => 'documents/noa/notice.pdf',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notices-of-award — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/notices-of-award')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_notices_of_award(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/notices-of-award')
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
    // POST /api/v1/notices-of-award — store
    // -------------------------------------------------------------------------

    public function test_store_creates_notice_of_award(): void
    {
        $officer    = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/notices-of-award', [
                'noa_number'        => 'NOA-2026-001',
                'bac_resolution_id' => $resolution->id,
                'awarded_supplier'  => 'Winning Corp.',
                'awarded_amount'    => 75000.00,
                'file_path'         => 'documents/noa/award.pdf',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.noa_number', 'NOA-2026-001')
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/notices-of-award', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['noa_number', 'bac_resolution_id', 'awarded_supplier', 'awarded_amount', 'file_path']]);
    }

    public function test_store_returns_422_when_resolution_already_has_noa(): void
    {
        $officer    = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $this->createNoticeOfAward($resolution);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/notices-of-award', [
                'noa_number'        => 'NOA-DUPLICATE',
                'bac_resolution_id' => $resolution->id,
                'awarded_supplier'  => 'Duplicate Corp.',
                'awarded_amount'    => 10000.00,
                'file_path'         => 'documents/dup.pdf',
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['bac_resolution_id']]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notices-of-award/{noticeOfAward} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_notice_of_award(): void
    {
        $officer    = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $noa        = $this->createNoticeOfAward($resolution);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/notices-of-award/{$noa->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $noa->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_notice_of_award(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/notices-of-award/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/notices-of-award/{noticeOfAward} — update
    // -------------------------------------------------------------------------

    public function test_update_modifies_notice_of_award(): void
    {
        $officer    = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $noa        = $this->createNoticeOfAward($resolution);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/notices-of-award/{$noa->id}", [
                'awarded_supplier' => 'Updated Winner Corp.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.awarded_supplier', 'Updated Winner Corp.');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/notices-of-award/{noticeOfAward} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_notice_of_award(): void
    {
        $officer    = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $noa        = $this->createNoticeOfAward($resolution);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/notices-of-award/{$noa->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('notices_of_award', ['id' => $noa->id]);
    }
}
