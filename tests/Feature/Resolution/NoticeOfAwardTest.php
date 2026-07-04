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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NoticeOfAwardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Fake the private disk so no real filesystem I/O occurs in tests.
        Storage::fake('private');
    }

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

        $abstract = AbstractOfQuotation::create([
            'rfq_id' => $rfq->id,
            'prepared_by_id' => $user->id,
            'status' => 'approved',
        ]);

        return BacResolution::create([
            'resolution_number' => "BAC-{$seq}",
            'abstract_of_quotation_id' => $abstract->id,
            'prepared_by_id' => $user->id,
            'file_path' => 'documents/bac/resolution.pdf',
        ]);
    }

    private function createNoticeOfAward(BacResolution $resolution): NoticeOfAward
    {
        static $seq = 0;
        $seq++;

        $file = UploadedFile::fake()->create('notice.pdf', 100, 'application/pdf');

        return NoticeOfAward::create([
            'noa_number' => "NOA-{$seq}",
            'bac_resolution_id' => $resolution->id,
            'awarded_supplier' => 'Best Supplier Corp.',
            'awarded_amount' => 50000.00,
            'file_path' => $file->store("notices-of-award/{$resolution->id}", 'private'),
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

    public function test_index_filters_by_bac_resolution_id(): void
    {
        $officer = $this->procurementOfficer();
        $resA = $this->createBacResolution($officer);
        $resB = $this->createBacResolution($officer);
        $this->createNoticeOfAward($resA);
        $this->createNoticeOfAward($resB);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/notices-of-award?bac_resolution_id={$resA->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_search_supplier(): void
    {
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $this->createNoticeOfAward($resolution);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/notices-of-award?search=Best');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/notices-of-award — store
    // -------------------------------------------------------------------------

    public function test_store_creates_notice_of_award(): void
    {
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $file = UploadedFile::fake()->create('award.pdf', 100, 'application/pdf');

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/notices-of-award', [
                'noa_number' => 'NOA-2026-001',
                'bac_resolution_id' => $resolution->id,
                'awarded_supplier' => 'Winning Corp.',
                'awarded_amount' => 75000.00,
                'file' => $file,
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
            ->assertJsonStructure(['errors' => ['noa_number', 'bac_resolution_id', 'awarded_supplier', 'awarded_amount', 'file']]);
    }

    public function test_store_returns_422_when_resolution_already_has_noa(): void
    {
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $this->createNoticeOfAward($resolution);
        $file = UploadedFile::fake()->create('dup.pdf', 100, 'application/pdf');

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/notices-of-award', [
                'noa_number' => 'NOA-DUPLICATE',
                'bac_resolution_id' => $resolution->id,
                'awarded_supplier' => 'Duplicate Corp.',
                'awarded_amount' => 10000.00,
                'file' => $file,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['bac_resolution_id']]);
    }

    // -------------------------------------------------------------------------
    // File upload / download
    // -------------------------------------------------------------------------

    public function test_file_path_not_exposed_but_download_url_present(): void
    {
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $file = UploadedFile::fake()->create('award.pdf', 100, 'application/pdf');

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/notices-of-award', [
                'noa_number' => 'NOA-2026-002',
                'bac_resolution_id' => $resolution->id,
                'awarded_supplier' => 'Winning Corp.',
                'awarded_amount' => 75000.00,
                'file' => $file,
            ]);

        $response->assertStatus(201);
        $this->assertArrayNotHasKey('file_path', $response->json('data'));
        $this->assertNotNull($response->json('data.download_url'));
    }

    public function test_can_download_notice_of_award_document(): void
    {
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $noa = $this->createNoticeOfAward($resolution);

        $response = $this->actingAs($officer, 'sanctum')
            ->get("/api/v1/notices-of-award/{$noa->id}/download");

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('Content-Disposition', ''),
        );
    }

    public function test_download_returns_401_when_unauthenticated(): void
    {
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $noa = $this->createNoticeOfAward($resolution);

        $this->getJson("/api/v1/notices-of-award/{$noa->id}/download")
            ->assertStatus(401);
    }

    public function test_file_deleted_from_disk_on_destroy(): void
    {
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $noa = $this->createNoticeOfAward($resolution);
        $path = $noa->file_path;

        Storage::disk('private')->assertExists($path);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/notices-of-award/{$noa->id}")
            ->assertStatus(200);

        Storage::disk('private')->assertMissing($path);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notices-of-award/{noticeOfAward} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_notice_of_award(): void
    {
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $noa = $this->createNoticeOfAward($resolution);

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
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $noa = $this->createNoticeOfAward($resolution);

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
        $officer = $this->procurementOfficer();
        $resolution = $this->createBacResolution($officer);
        $noa = $this->createNoticeOfAward($resolution);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/notices-of-award/{$noa->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('notices_of_award', ['id' => $noa->id]);
    }
}
