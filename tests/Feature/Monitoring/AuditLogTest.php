<?php

declare(strict_types=1);

namespace Tests\Feature\Monitoring;

use App\Models\AuditLog;
use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    private function procurementOfficer(): User
    {
        $role = Role::where('name', 'procurement_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function budgetOfficer(): User
    {
        $role = Role::where('name', 'budget_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function requester(): User
    {
        $role = Role::where('name', 'requester')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function officeId(): int
    {
        return Office::where('code', 'ORM')->value('id');
    }

    private function createAuditLog(User $user): AuditLog
    {
        $pr = PurchaseRequest::create([
            'requester_id' => $user->id,
            'requesting_office_id' => $this->officeId(),
            'purpose' => 'Test PR',
            'status' => 'draft',
        ]);

        return AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => PurchaseRequest::class,
            'auditable_id' => $pr->id,
            'event' => 'updated',
            'field' => 'status',
            'old_value' => 'draft',
            'new_value' => 'submitted',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/audit-logs — index (read-only, procurement_officer + budget_officer)
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/audit-logs')
            ->assertStatus(401);
    }

    public function test_index_returns_audit_logs_for_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $this->createAuditLog($officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/audit-logs')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_index_returns_audit_logs_for_budget_officer(): void
    {
        $officer = $this->budgetOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/audit-logs')
            ->assertStatus(200);
    }

    public function test_index_returns_403_for_requester(): void
    {
        $requester = $this->requester();

        $this->actingAs($requester, 'sanctum')
            ->getJson('/api/v1/audit-logs')
            ->assertStatus(403);
    }

    public function test_index_filters_by_event(): void
    {
        $officer = $this->procurementOfficer();
        $this->createAuditLog($officer);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/audit-logs?event=updated');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_index_filters_by_user_id(): void
    {
        $officerA = $this->procurementOfficer();
        $officerB = $this->procurementOfficer();
        $this->createAuditLog($officerA);
        $this->createAuditLog($officerB);

        $response = $this->actingAs($officerA, 'sanctum')
            ->getJson("/api/v1/audit-logs?user_id={$officerA->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/audit-logs/{auditLog} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_audit_log(): void
    {
        $officer = $this->procurementOfficer();
        $log = $this->createAuditLog($officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/audit-logs/{$log->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.event', 'updated')
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_audit_log(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/audit-logs/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Write methods are not registered (read-only resource)
    // -------------------------------------------------------------------------

    public function test_post_to_audit_logs_is_not_found(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/audit-logs', [])
            ->assertStatus(405);
    }

    public function test_delete_audit_log_is_not_found(): void
    {
        $officer = $this->procurementOfficer();
        $log = $this->createAuditLog($officer);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/audit-logs/{$log->id}")
            ->assertStatus(405);
    }
}
