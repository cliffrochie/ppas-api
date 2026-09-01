<?php

declare(strict_types=1);

namespace Tests\Feature\Monitoring;

use App\Models\Notification;
use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    private function officeId(): int
    {
        return Office::where('code', 'ORM')->value('id');
    }

    private function createNotification(User $recipient, User $actor): Notification
    {
        static $seq = 0;
        $seq++;

        $pr = PurchaseRequest::create([
            'requester_id' => $actor->id,
            'requesting_office_id' => $this->officeId(),
            'purpose' => "Test PR #{$seq}",
            'status' => 'submitted',
        ]);

        return Notification::create([
            'user_id' => $recipient->id,
            'purchase_request_id' => $pr->id,
            'type' => 'pr_status_changed',
            'title' => 'PR Submitted',
            'message' => 'A purchase request was submitted.',
            'is_read' => false,
            'created_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notifications — index (scoped to auth user)
    // -------------------------------------------------------------------------

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/notifications')
            ->assertStatus(401);
    }

    public function test_index_returns_only_authenticated_users_notifications(): void
    {
        $officer1 = $this->procurementOfficer();
        $officer2 = $this->procurementOfficer();

        $this->createNotification($officer1, $officer2);
        $this->createNotification($officer2, $officer1);

        $response = $this->actingAs($officer1, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonPath('errors', null);

        // Only officer1's notification should be in the list
        $ids = collect($response->json('data'))->pluck('user_id')->unique()->all();
        $this->assertSame([$officer1->id], $ids);
    }

    public function test_index_filters_by_is_read(): void
    {
        $officer = $this->procurementOfficer();
        $actor = $this->procurementOfficer();
        $notif = $this->createNotification($officer, $actor);

        $notif->forceFill(['is_read' => true])->save();

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/notifications?is_read=0');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_index_filters_by_type(): void
    {
        $officer = $this->procurementOfficer();
        $actor = $this->procurementOfficer();
        $this->createNotification($officer, $actor);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/notifications?type=pr_status_changed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_index_filters_by_search_matches_title(): void
    {
        $officer = $this->procurementOfficer();
        $actor = $this->procurementOfficer();
        $this->createNotification($officer, $actor); // title: "PR Submitted"

        Notification::create([
            'user_id' => $officer->id,
            'type' => 'pr_status_changed',
            'title' => 'Budget Certified',
            'message' => 'Funds are available.',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/notifications?search=Submitted');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsStringIgnoringCase('submitted', $data[0]['title']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/notifications/{notification} — show
    // -------------------------------------------------------------------------

    public function test_show_returns_own_notification(): void
    {
        $officer = $this->procurementOfficer();
        $notification = $this->createNotification($officer, $officer);

        $this->actingAs($officer, 'sanctum')
            ->getJson("/api/v1/notifications/{$notification->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('errors', null);
    }

    public function test_show_returns_403_for_another_users_notification(): void
    {
        $requester1 = $this->requester();
        $requester2 = $this->requester();
        $notification = $this->createNotification($requester2, $requester1);

        $this->actingAs($requester1, 'sanctum')
            ->getJson("/api/v1/notifications/{$notification->id}")
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/v1/notifications/{notification}/mark-read
    // -------------------------------------------------------------------------

    public function test_mark_read_sets_read_at_timestamp(): void
    {
        $officer = $this->procurementOfficer();
        $notification = $this->createNotification($officer, $officer);

        $this->assertNull($notification->read_at);

        $this->actingAs($officer, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notification->id}/mark-read")
            ->assertStatus(200)
            ->assertJsonPath('data.is_read', true);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_mark_read_returns_403_for_another_users_notification(): void
    {
        $requester1 = $this->requester();
        $requester2 = $this->requester();
        $notification = $this->createNotification($requester2, $requester1);

        $this->actingAs($requester1, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notification->id}/mark-read")
            ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Write methods are not registered
    // -------------------------------------------------------------------------

    public function test_post_to_notifications_is_not_found(): void
    {
        $officer = $this->procurementOfficer();

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/notifications', [])
            ->assertStatus(405);
    }
}
