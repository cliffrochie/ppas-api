<?php

declare(strict_types=1);

namespace Tests\Feature\RFQ;

use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RfqTest extends TestCase
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

    private function createPurchaseRequest(User $user): PurchaseRequest
    {
        static $prSeq = 0;
        $prSeq++;

        return PurchaseRequest::create([
            'requester_id' => $user->id,
            'requesting_office_id' => $this->officeId(),
            'purpose' => "Test PR #{$prSeq}",
            'status' => 'pr_approved',
        ]);
    }

    private function createRfq(PurchaseRequest $pr, User $user): Rfq
    {
        static $seq = 0;
        $seq++;

        return Rfq::create([
            'purchase_request_id' => $pr->id,
            'prepared_by_id' => $user->id,
            'rfq_number' => sprintf('RFQ-%d-%05d', now()->year, $seq),
            'status' => 'draft',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/rfqs — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/rfqs')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_rfqs(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/rfqs')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_index_filters_by_status(): void
    {
        $officer = $this->procurementOfficer();
        $prA = $this->createPurchaseRequest($officer);
        $prB = $this->createPurchaseRequest($officer);
        $rfqA = $this->createRfq($prA, $officer);
        $rfqB = $this->createRfq($prB, $officer);
        $rfqB->forceFill(['status' => 'signed'])->save();

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/rfqs?status=signed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('signed', $response->json('data.0.status'));
    }

    public function test_index_filters_by_purchase_request_id(): void
    {
        $officer = $this->procurementOfficer();
        $prA = $this->createPurchaseRequest($officer);
        $prB = $this->createPurchaseRequest($officer);
        $this->createRfq($prA, $officer);
        $this->createRfq($prB, $officer);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/rfqs?purchase_request_id={$prA->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/rfqs — store
    // -------------------------------------------------------------------------

    public function test_store_creates_rfq(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/rfqs', [
                'purchase_request_id' => $pr->id,
                'prepared_by_id' => $officer->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('errors', null);
    }

    public function test_store_returns_422_when_required_fields_are_missing(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/rfqs', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['purchase_request_id', 'prepared_by_id']]);
    }

    public function test_store_returns_422_when_pr_already_has_rfq(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $this->createRfq($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/rfqs', [
                'purchase_request_id' => $pr->id,
                'prepared_by_id' => $officer->id,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['purchase_request_id']]);
    }

    // -------------------------------------------------------------------------
    // File upload / download
    // -------------------------------------------------------------------------

    public function test_store_with_file_upload_sets_download_url(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $file = UploadedFile::fake()->create('rfq.pdf', 100, 'application/pdf');

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/rfqs', [
                'purchase_request_id' => $pr->id,
                'prepared_by_id' => $officer->id,
                'file' => $file,
            ]);

        $response->assertStatus(201);
        $this->assertArrayNotHasKey('file_path', $response->json('data'));
        $this->assertNotNull($response->json('data.download_url'));
    }

    public function test_download_url_is_null_when_no_file_uploaded(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $rfq = $this->createRfq($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/rfqs/{$rfq->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.download_url', null);
    }

    public function test_can_download_rfq_document(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $file = UploadedFile::fake()->create('rfq.pdf', 100, 'application/pdf');

        $rfq = Rfq::create([
            'purchase_request_id' => $pr->id,
            'prepared_by_id' => $officer->id,
            'rfq_number' => sprintf('RFQ-%d-99999', now()->year),
            'status' => 'draft',
            'file_path' => $file->store("rfqs/{$pr->id}", 'private'),
        ]);

        $response = $this->actingAs($officer, 'sanctum')
            ->get("/api/v1/rfqs/{$rfq->id}/download");

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('Content-Disposition', ''),
        );
    }

    public function test_download_returns_404_when_no_file_uploaded(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $rfq = $this->createRfq($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/rfqs/{$rfq->id}/download")
            ->assertStatus(404);
    }

    public function test_download_returns_401_when_unauthenticated(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $rfq = $this->createRfq($pr, $officer);

        $this->getJson("/api/v1/rfqs/{$rfq->id}/download")
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/rfqs/{rfq} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_rfq(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $rfq = $this->createRfq($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/rfqs/{$rfq->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $rfq->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_rfq(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/rfqs/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/rfqs/{rfq} — update
    // -------------------------------------------------------------------------

    public function test_update_modifies_rfq(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $rfq = $this->createRfq($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/rfqs/{$rfq->id}", [
                'status' => 'for_signature',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'for_signature');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/rfqs/{rfq} — destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_rfq(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $rfq = $this->createRfq($pr, $officer);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/rfqs/{$rfq->id}")
            ->assertStatus(200)
            ->assertJsonPath('data', null);

        $this->assertDatabaseMissing('rfqs', ['id' => $rfq->id]);
    }

    // -------------------------------------------------------------------------
    // Auto-generated rfq_number
    // -------------------------------------------------------------------------

    public function test_rfq_number_is_auto_generated_on_create(): void
    {
        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $year = now()->year;
        $month = now()->format('m');

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/rfqs', [
                'purchase_request_id' => $pr->id,
                'prepared_by_id' => $officer->id,
            ]);

        $response->assertStatus(201);
        $this->assertMatchesRegularExpression(
            "/^RFQ-{$year}-{$month}-\d{3}$/",
            $response->json('data.rfq_number')
        );
    }
}
