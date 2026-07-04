<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\Office;
use App\Models\PrAttachment;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrAttachmentTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Test helpers
    // -------------------------------------------------------------------------

    private function procurementOfficer(): User
    {
        $role = Role::where('name', 'procurement_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function requester(): User
    {
        $role = Role::where('name', 'requester')->firstOrFail();
        $office = Office::where('code', 'ORM')->value('id');

        return User::factory()->create([
            'role_id' => $role->id,
            'office_id' => $office,
        ]);
    }

    private function officeId(): int
    {
        return Office::where('code', 'ORM')->value('id');
    }

    private function createPurchaseRequest(User $user): PurchaseRequest
    {
        return PurchaseRequest::create([
            'requester_id' => $user->id,
            'requesting_office_id' => $this->officeId(),
            'purpose' => 'Test procurement purpose',
            'status' => 'draft',
        ]);
    }

    /**
     * Create a PrAttachment record backed by a real fake file on the private disk.
     */
    private function createAttachment(User $uploader, PurchaseRequest $pr): PrAttachment
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $path = $file->store("pr-attachments/{$pr->id}", 'private');

        return PrAttachment::create([
            'purchase_request_id' => $pr->id,
            'uploader_id' => $uploader->id,
            'type' => 'other',
            'file_name' => 'document.pdf',
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/pr-attachments — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/pr-attachments')
            ->assertStatus(401);
    }

    public function test_index_filters_by_purchase_request_id(): void
    {
        Storage::fake('private');

        $officer = $this->procurementOfficer();
        $prA = $this->createPurchaseRequest($officer);
        $prB = $this->createPurchaseRequest($officer);
        $this->createAttachment($officer, $prA);
        $this->createAttachment($officer, $prB);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/pr-attachments?purchase_request_id={$prA->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_type(): void
    {
        Storage::fake('private');

        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');
        $path = $file->store("pr-attachments/{$pr->id}", 'private');

        PrAttachment::create([
            'purchase_request_id' => $pr->id,
            'uploader_id' => $officer->id,
            'type' => 'signed_pr',
            'file_name' => 'doc.pdf',
            'file_path' => $path,
            'file_size' => 50,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);

        $this->createAttachment($officer, $pr); // type = 'other'

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/pr-attachments?type=signed_pr');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/pr-attachments — store (upload)
    // -------------------------------------------------------------------------

    public function test_can_upload_attachment(): void
    {
        // Fake the private disk so no real filesystem I/O occurs in the test.
        Storage::fake('private');

        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/pr-attachments', [
                'purchase_request_id' => $pr->id,
                'type' => 'other',
                'file' => $file,
            ]);

        $response->assertStatus(201);

        // Assert the file was stored on the private disk.
        $filePath = $response->json('data.file_name');
        Storage::disk('private')->assertExists("pr-attachments/{$pr->id}/{$file->hashName()}");
    }

    public function test_file_path_not_exposed_in_response(): void
    {
        Storage::fake('private');

        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $file = UploadedFile::fake()->create('report.pdf', 50, 'application/pdf');

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/pr-attachments', [
                'purchase_request_id' => $pr->id,
                'type' => 'other',
                'file' => $file,
            ]);

        $response->assertStatus(201);

        // file_path must never appear in the API response.
        $this->assertArrayNotHasKey('file_path', $response->json('data'));
    }

    public function test_download_url_present_in_response(): void
    {
        Storage::fake('private');

        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $file = UploadedFile::fake()->create('spec.pdf', 50, 'application/pdf');

        $response = $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/pr-attachments', [
                'purchase_request_id' => $pr->id,
                'type' => 'other',
                'file' => $file,
            ]);

        $response->assertStatus(201);
        $this->assertArrayHasKey('download_url', $response->json('data'));
        $this->assertNotNull($response->json('data.download_url'));
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/pr-attachments/{id}/download
    // -------------------------------------------------------------------------

    public function test_can_download_attachment(): void
    {
        Storage::fake('private');

        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $attachment = $this->createAttachment($officer, $pr);

        $response = $this->actingAs($officer, 'sanctum')
            ->get("/api/v1/pr-attachments/{$attachment->id}/download");

        // A successful download returns a streamed file response (200).
        $response->assertStatus(200);
        // Content-Disposition header confirms this is a file download, not JSON.
        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('Content-Disposition', ''),
        );
    }

    public function test_unauthenticated_cannot_download(): void
    {
        Storage::fake('private');

        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $attachment = $this->createAttachment($officer, $pr);

        $this->getJson("/api/v1/pr-attachments/{$attachment->id}/download")
            ->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/pr-attachments/{id}
    // -------------------------------------------------------------------------

    public function test_file_deleted_from_disk_on_destroy(): void
    {
        Storage::fake('private');

        $officer = $this->procurementOfficer();
        $pr = $this->createPurchaseRequest($officer);
        $attachment = $this->createAttachment($officer, $pr);
        $filePath = $attachment->file_path;

        // Confirm the file exists before deletion.
        Storage::disk('private')->assertExists($filePath);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/pr-attachments/{$attachment->id}")
            ->assertStatus(200);

        // File must be gone from the private disk after deletion.
        Storage::disk('private')->assertMissing($filePath);
    }
}
