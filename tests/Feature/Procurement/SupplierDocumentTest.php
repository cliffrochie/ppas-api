<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\Role;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierDocumentTest extends TestCase
{
    private function procurementOfficer(): User
    {
        $role = Role::where('name', 'procurement_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createSupplier(): Supplier
    {
        return Supplier::create([
            'name' => 'Test Supplier Corp.',
            'email' => 'supplier@test.com',
            'is_active' => true,
        ]);
    }

    private function createDocument(User $uploader, Supplier $supplier, string $fileName): SupplierDocument
    {
        $file = UploadedFile::fake()->create($fileName, 50, 'application/pdf');

        return SupplierDocument::create([
            'supplier_id' => $supplier->id,
            'uploader_id' => $uploader->id,
            'file_name' => $fileName,
            'file_path' => $file->store("supplier-documents/{$supplier->id}", 'private'),
            'file_size' => 50,
            'mime_type' => 'application/pdf',
            'uploaded_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/supplier-documents — index
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/supplier-documents')
            ->assertStatus(401);
    }

    public function test_index_returns_paginated_supplier_documents(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/supplier-documents')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_index_filters_by_search_matches_file_name(): void
    {
        Storage::fake('private');

        $officer = $this->procurementOfficer();
        $supplier = $this->createSupplier();

        $this->createDocument($officer, $supplier, 'business-permit.pdf');
        $this->createDocument($officer, $supplier, 'tax-clearance.pdf');

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/supplier-documents?search=permit');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsStringIgnoringCase('permit', $response->json('data.0.file_name'));
    }
}
