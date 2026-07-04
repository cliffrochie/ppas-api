<?php

declare(strict_types=1);

namespace Tests\Feature\Monitoring;

use App\Models\LoginLog;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class LoginLogTest extends TestCase
{
    private function procurementOfficer(): User
    {
        $role = Role::where('name', 'procurement_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function requester(): User
    {
        $role = Role::where('name', 'requester')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createLoginLog(User $user): LoginLog
    {
        return LoginLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/login-logs — index (read-only, procurement_officer only)
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/login-logs')
            ->assertStatus(401);
    }

    public function test_index_returns_login_logs_for_procurement_officer(): void
    {
        $officer = $this->procurementOfficer();
        $this->createLoginLog($officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/login-logs')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'message',
                'errors',
            ])
            ->assertJsonPath('errors', null);
    }

    public function test_index_returns_403_for_requester(): void
    {
        $requester = $this->requester();

        $this->actingAs($requester, 'sanctum')
            ->getJson('/api/v1/login-logs')
            ->assertStatus(403);
    }

    public function test_index_filters_by_status(): void
    {
        $officer = $this->procurementOfficer();
        $this->createLoginLog($officer);
        LoginLog::create([
            'user_id' => $officer->id,
            'email' => $officer->email,
            'status' => 'failed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/login-logs?status=success');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
        foreach ($response->json('data') as $row) {
            $this->assertEquals('success', $row['status']);
        }
    }

    public function test_index_filters_by_search_email(): void
    {
        $officer = $this->procurementOfficer();
        $this->createLoginLog($officer);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/login-logs?search={$officer->email}");

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/login-logs/{loginLog} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_login_log(): void
    {
        $officer = $this->procurementOfficer();
        $log = $this->createLoginLog($officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/login-logs/{$log->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_404_for_missing_login_log(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/login-logs/999999')
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Write methods are not registered (read-only resource)
    // -------------------------------------------------------------------------

    public function test_post_to_login_logs_is_not_found(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/login-logs', [])
            ->assertStatus(405);
    }

    public function test_delete_login_log_is_not_found(): void
    {
        $officer = $this->procurementOfficer();
        $log = $this->createLoginLog($officer);

        $this->actingAs($officer, 'sanctum')
            ->deleteJson("/api/v1/login-logs/{$log->id}")
            ->assertStatus(405);
    }
}
