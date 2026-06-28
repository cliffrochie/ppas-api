<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    // -------------------------------------------------------------------------
    // GET /api/v1/dashboard — index
    // -------------------------------------------------------------------------

    public function test_dashboard_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertStatus(401);
    }

    public function test_dashboard_returns_expected_structure(): void
    {
        $officer = $this->procurementOfficer();
        $year    = now()->year;

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/dashboard?year={$year}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'kpi' => [
                        'total_requests',
                        'pending_requests',
                        'approved_requests',
                        'completed_requests',
                    ],
                    'budget_utilization_by_month' => [
                        'months',
                        'grand_total',
                    ],
                    'requests_per_section',
                    'budget_per_section',
                    'requests_per_category',
                    'recent_requests',
                    'high_value_requests',
                ],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_dashboard_returns_422_for_invalid_year(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/dashboard?year=1800')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['year']]);
    }

    public function test_dashboard_kpi_excludes_drafts(): void
    {
        $officer = $this->procurementOfficer();
        $year    = now()->year;

        // Draft — must be excluded from all KPI counts
        PurchaseRequest::create([
            'requester_id'         => $officer->id,
            'requesting_office_id' => $this->officeId(),
            'purpose'              => 'Draft request',
            'status'               => 'draft',
        ]);

        // Submitted — must appear in pending count
        PurchaseRequest::create([
            'requester_id'         => $officer->id,
            'requesting_office_id' => $this->officeId(),
            'purpose'              => 'Submitted request',
            'status'               => 'submitted',
        ]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/dashboard?year={$year}");

        $response->assertStatus(200);

        $kpi = $response->json('data.kpi');
        $this->assertSame(1, $kpi['total_requests'], 'Draft must not count toward total.');
        $this->assertSame(1, $kpi['pending_requests'], 'Submitted must count as pending.');
    }

    public function test_dashboard_budget_by_month_covers_all_12_months(): void
    {
        $officer = $this->procurementOfficer();
        $year    = now()->year;

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/dashboard?year={$year}");

        $response->assertStatus(200);
        $months = $response->json('data.budget_utilization_by_month.months');
        $this->assertCount(12, $months);
    }

    public function test_dashboard_filters_by_office_id(): void
    {
        $officer  = $this->procurementOfficer();
        $year     = now()->year;
        $officeId = $this->officeId();

        PurchaseRequest::create([
            'requester_id'         => $officer->id,
            'requesting_office_id' => $officeId,
            'purpose'              => 'Office-specific request',
            'status'               => 'submitted',
        ]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/dashboard?year={$year}&office_id={$officeId}");

        $response->assertStatus(200);
        $kpi = $response->json('data.kpi');
        $this->assertGreaterThanOrEqual(1, $kpi['total_requests']);
    }
}
