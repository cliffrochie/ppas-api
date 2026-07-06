<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class NotificationService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Notification::where('user_id', auth()->id())
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when(array_key_exists('is_read', $filters), fn ($q) => $q->where('is_read', $filters['is_read']))
            ->when($filters['purchase_request_id'] ?? null, fn ($q, $v) => $q->where('purchase_request_id', $v))
            ->latest()
            ->paginate(15);
    }

    public function markRead(Notification $notification): Notification
    {
        return DB::transaction(function () use ($notification): Notification {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            return $notification->refresh();
        });
    }

    /**
     * Create in-system notification records for every relevant recipient
     * when a Purchase Request status changes.
     *
     * The routing table maps each destination status to:
     *   - 'audience': 'role' (all users of that role) or 'requester' (PR owner)
     *   - 'role': the role name when audience = 'role'
     *   - 'type', 'title', 'message': notification content
     *
     * The actor who triggered the transition is always excluded so users
     * do not receive self-referential notifications.
     */
    public function notifyStatusChange(
        PurchaseRequest $purchaseRequest,
        string $fromStatus,
        string $toStatus,
        ?int $actorId,
    ): void {
        $routing = $this->resolveRouting($toStatus);

        if ($routing === null) {
            return;
        }

        $recipientIds = match ($routing['audience']) {
            'role' => User::whereHas('role', fn ($q) => $q->where('name', $routing['role']))
                ->pluck('id')
                ->toArray(),
            'requester' => [$purchaseRequest->requester_id],
            default => [],
        };

        foreach ($recipientIds as $userId) {
            // Never notify the actor who triggered the transition.
            if ($userId === $actorId) {
                continue;
            }

            Notification::create([
                'user_id' => $userId,
                'purchase_request_id' => $purchaseRequest->id,
                'type' => $routing['type'],
                'title' => $routing['title'],
                'message' => $routing['message'],
                'is_read' => false,
            ]);
        }
    }

    /**
     * Return the routing definition for a given destination status,
     * or null if no notification should be sent for that transition.
     *
     * @return array{audience: string, role?: string, type: string, title: string, message: string}|null
     */
    private function resolveRouting(string $toStatus): ?array
    {
        return match ($toStatus) {
            'submitted' => [
                'audience' => 'role',
                'role' => 'bac_secretariat',
                'type' => 'pr_submitted',
                'title' => 'New Request Submitted',
                'message' => 'A new procurement request has been submitted and is awaiting BAC Secretariat review.',
            ],
            'under_review' => [
                'audience' => 'requester',
                'type' => 'pr_under_review',
                'title' => 'Request Under Review',
                'message' => 'Your request is now under review by the BAC Secretariat.',
            ],
            'returned' => [
                'audience' => 'requester',
                'type' => 'pr_returned',
                'title' => 'Request Returned',
                'message' => 'Your request has been returned. Please review the remarks and resubmit.',
            ],
            'for_budget_approval' => [
                'audience' => 'role',
                'role' => 'budget_officer',
                'type' => 'pr_for_budget',
                'title' => 'Request Awaiting Budget Approval',
                'message' => 'A procurement request has been forwarded for your budget approval.',
            ],
            'disapproved' => [
                'audience' => 'requester',
                'type' => 'pr_disapproved',
                'title' => 'Request Disapproved',
                'message' => 'Your procurement request has been disapproved by the Budget Officer.',
            ],
            'budget_approved' => [
                'audience' => 'requester',
                'type' => 'pr_budget_approved',
                'title' => 'Request Budget-Approved',
                'message' => 'Your procurement request has been approved by the Budget Officer.',
            ],
            'forwarded_to_ppu' => [
                'audience' => 'role',
                'role' => 'procurement_officer',
                'type' => 'pr_forwarded_to_ppu',
                'title' => 'Request Forwarded to PPU',
                'message' => 'A budget-approved request is ready for Purchase Request preparation.',
            ],
            'pr_prepared' => [
                'audience' => 'requester',
                'type' => 'pr_prepared',
                'title' => 'Purchase Request Prepared',
                'message' => 'Your Purchase Request has been assigned a PR number and is being routed for signatures.',
            ],
            'pr_approved' => [
                'audience' => 'requester',
                'type' => 'pr_approved',
                'title' => 'Purchase Request Approved',
                'message' => 'Your Purchase Request has been signed and approved.',
            ],
            'po_prepared' => [
                'audience' => 'requester',
                'type' => 'po_generated',
                'title' => 'Purchase Order Prepared',
                'message' => 'A Purchase Order has been generated for your request.',
            ],
            'completed' => [
                'audience' => 'requester',
                'type' => 'pr_completed',
                'title' => 'Request Completed',
                'message' => 'Your procurement request has been completed successfully.',
            ],
            // Intermediate canvassing/resolution stages: no end-user notification needed.
            default => null,
        };
    }
}
