<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidStatusTransitionException;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PurchaseRequestService
{
    /**
     * Valid next-statuses for each current status.
     * An empty array means the status is terminal — no further transitions allowed.
     */
    private const ALLOWED_TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['under_review', 'returned'],
        'under_review' => ['for_budget_approval', 'returned'],
        'returned' => ['submitted'],
        'for_budget_approval' => ['budget_approved', 'disapproved'],
        'disapproved' => [],
        'budget_approved' => ['forwarded_to_ppu'],
        'forwarded_to_ppu' => ['pr_prepared'],
        'pr_prepared' => ['pr_approved'],
        'pr_approved' => ['rfq_prepared'],
        'rfq_prepared' => ['canvassing'],
        'canvassing' => ['abstract_prepared'],
        'abstract_prepared' => ['bac_resolution_noa'],
        'bac_resolution_noa' => ['po_prepared'],
        'po_prepared' => ['completed'],
        'completed' => [],
    ];

    /**
     * Fields tracked by audit logging on created/updated events.
     * Excludes requester_id (ownership immutable), id, created_at, updated_at.
     */
    private const AUDITED_FIELDS = [
        'purpose',
        'status',
        'category_id',
        'requesting_office_id',
        'alobs_number',
        'total_amount',
        'submitted_at',
        'rf_number',
        'pr_number',
    ];

    public function __construct(
        private readonly Request $request,
        private readonly PrStatusHistoryService $prStatusHistoryService,
        private readonly NotificationService $notificationService,
    ) {
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Assert that transitioning from $current to $next is permitted.
     *
     * @throws InvalidStatusTransitionException
     */
    private function assertValidTransition(string $current, string $next): void
    {
        $allowed = self::ALLOWED_TRANSITIONS[$current] ?? [];

        if (!in_array($next, $allowed, true)) {
            throw new InvalidStatusTransitionException($current, $next);
        }
    }

    /** Resolve the current authenticated user id (null when unauthenticated). */
    private function actorId(): ?int
    {
        return $this->request->user()?->id;
    }

    /** Resolve the request IP address. */
    private function actorIp(): ?string
    {
        return $this->request->ip();
    }

    // -------------------------------------------------------------------------
    // Public CRUD methods
    // -------------------------------------------------------------------------

    public function list(User $user): LengthAwarePaginator
    {
        $query = PurchaseRequest::with(['requester', 'requestingOffice', 'category'])
            ->latest();

        if ($user->role?->name === 'requester') {
            // Requesters see only their own PRs (including drafts).
            $query->where('requester_id', $user->id);
        } else {
            // Officers (budget_officer, procurement_officer) see all submitted PRs.
            // Drafts are private to the requester until submitted.
            $query->where('status', '!=', 'draft');
        }

        return $query->paginate(15);
    }

    public function show(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        return $purchaseRequest->load(['requester', 'requestingOffice', 'category', 'items', 'attachments']);
    }

    public function store(array $validated): PurchaseRequest
    {
        return DB::transaction(function () use ($validated): PurchaseRequest {
            // rf_number is NOT generated here — it is generated when the Requester
            // transitions draft → submitted (per schema-v1.md: "auto-generated when
            // draft is submitted"). Drafts may be abandoned, so assigning an RF #
            // at creation would waste sequence numbers.
            $purchaseRequest = PurchaseRequest::create($validated);

            // Audit: one row per non-null audited field that was just created.
            $this->auditCreated($purchaseRequest);

            return $purchaseRequest;
        });
    }

    public function update(PurchaseRequest $purchaseRequest, array $validated): PurchaseRequest
    {
        // Guard transition validity before entering the DB transaction.
        // This throws InvalidStatusTransitionException (→ 422) on an illegal jump.
        if (isset($validated['status'])) {
            $this->assertValidTransition($purchaseRequest->status, $validated['status']);
        }

        return DB::transaction(function () use ($purchaseRequest, $validated): PurchaseRequest {
            // Capture state before any write.
            $fromStatus = $purchaseRequest->status;

            $originalValues = collect(self::AUDITED_FIELDS)
                ->mapWithKeys(fn(string $field) => [$field => $purchaseRequest->getRawOriginal($field)])
                ->all();

            // ------------------------------------------------------------------
            // C4 fix — rf_number generated at draft → submitted, not at store().
            // Guard with rf_number === null so a resubmission (returned → submitted)
            // keeps its original RF #.
            // ------------------------------------------------------------------
            if (isset($validated['status']) && $validated['status'] === 'submitted') {
                $validated['submitted_at'] = now();

                if ($purchaseRequest->rf_number === null) {
                    $year = now()->year;

                    $last = PurchaseRequest::whereYear('created_at', $year)
                        ->whereNotNull('rf_number')
                        ->lockForUpdate()
                        ->max('rf_number');

                    $next = $last !== null ? ((int) substr($last, -5)) + 1 : 1;
                    // rf_number is not in #[Fillable] — forceFill writes it below.
                    $validated['rf_number'] = sprintf('RF-%d-%05d', $year, $next);
                }
            }

            // ------------------------------------------------------------------
            // C5 fix — pr_number generated at forwarded_to_ppu → pr_prepared,
            // not at submitted (schema-v1.md: "auto-generated when PPU prepares
            // the PR document").
            // ------------------------------------------------------------------
            if (isset($validated['status']) && $validated['status'] === 'pr_prepared') {
                if ($purchaseRequest->pr_number === null) {
                    $year = now()->year;

                    $last = PurchaseRequest::whereYear('created_at', $year)
                        ->whereNotNull('pr_number')
                        ->lockForUpdate()
                        ->max('pr_number');

                    $next = $last !== null ? ((int) substr($last, -5)) + 1 : 1;
                    // pr_number is not in #[Fillable] — forceFill writes it below.
                    $validated['pr_number'] = sprintf('PR-%d-%05d', $year, $next);
                }
            }

            // `remarks` is validated on the request but is not a column on
            // purchase_requests — it belongs only in pr_status_histories. Strip
            // it before forceFill to avoid writing a non-existent column.
            $remarks = $validated['remarks'] ?? null;
            unset($validated['remarks']);

            // Use forceFill so rf_number / pr_number (not in #[Fillable]) can be
            // written in one shot alongside the rest of the validated payload.
            $purchaseRequest->forceFill($validated)->save();
            $purchaseRequest->refresh()->load(['requester', 'requestingOffice', 'category']);

            // Audit: log changed fields and the dedicated status_changed event.
            $this->auditUpdated($purchaseRequest, $originalValues, $validated);

            // ------------------------------------------------------------------
            // C1 + C3 fix — write an immutable status history row on every
            // status transition. alobs_number is captured here so the Budget
            // Officer's encoding is permanently recorded (C3).
            // ------------------------------------------------------------------
            if (isset($validated['status'])) {
                $toStatus = $validated['status'];

                $this->prStatusHistoryService->record(
                    purchaseRequestId: $purchaseRequest->id,
                    actorId: $this->actorId() ?? 0,
                    fromStatus: $fromStatus,
                    toStatus: $toStatus,
                    remarks: $remarks,
                    alobsNumber: $validated['alobs_number'] ?? null,
                );

                // C2 fix — trigger in-system notifications for relevant actors.
                $this->notificationService->notifyStatusChange(
                    purchaseRequest: $purchaseRequest,
                    fromStatus: $fromStatus,
                    toStatus: $toStatus,
                    actorId: $this->actorId(),
                );
            }

            return $purchaseRequest;
        });
    }

    public function destroy(PurchaseRequest $purchaseRequest): void
    {
        DB::transaction(function () use ($purchaseRequest): void {
            $id = $purchaseRequest->getKey();
            $purchaseRequest->delete();

            // Audit: single row recording which record was deleted.
            AuditLogger::log(
                auditable: $purchaseRequest,
                event: 'deleted',
                field: 'id',
                oldValue: $id,
                newValue: null,
                userId: $this->actorId(),
                ipAddress: $this->actorIp(),
            );
        });
    }

    // -------------------------------------------------------------------------
    // Private audit helpers
    // -------------------------------------------------------------------------

    /**
     * Write one `created` audit row per non-null audited field after a store.
     */
    private function auditCreated(PurchaseRequest $purchaseRequest): void
    {
        $changes = [];

        foreach (self::AUDITED_FIELDS as $field) {
            $value = $purchaseRequest->getRawOriginal($field) ?? $purchaseRequest->getAttribute($field);

            if ($value !== null) {
                $changes[$field] = [null, $value];
            }
        }

        AuditLogger::logMany(
            auditable: $purchaseRequest,
            event: 'created',
            changes: $changes,
            userId: $this->actorId(),
            ipAddress: $this->actorIp(),
        );
    }

    /**
     * Write `updated` audit rows for every changed audited field, plus a
     * dedicated `status_changed` row when the status field is among them.
     *
     * @param array<string, mixed> $originalValues  Raw DB values before save
     * @param array<string, mixed> $validated        Fields passed to forceFill
     */
    private function auditUpdated(
        PurchaseRequest $purchaseRequest,
        array $originalValues,
        array $validated,
    ): void {
        $updatedChanges = [];
        $newStatusValue = null;
        $oldStatusValue = null;

        foreach (self::AUDITED_FIELDS as $field) {
            // Only examine fields that were part of this update payload
            // (or were injected internally, like pr_number / submitted_at).
            $newRaw = $purchaseRequest->getRawOriginal($field)
                ?? $purchaseRequest->getAttribute($field);

            $oldRaw = $originalValues[$field] ?? null;

            // Cast both sides to string for comparison (avoids type-mismatch false positives).
            $oldStr = $oldRaw !== null ? (string) $oldRaw : null;
            $newStr = $newRaw !== null ? (string) $newRaw : null;

            if ($oldStr !== $newStr) {
                $updatedChanges[$field] = [$oldRaw, $newRaw];

                if ($field === 'status') {
                    $oldStatusValue = $oldRaw;
                    $newStatusValue = $newRaw;
                }
            }
        }

        if (!empty($updatedChanges)) {
            AuditLogger::logMany(
                auditable: $purchaseRequest,
                event: 'updated',
                changes: $updatedChanges,
                userId: $this->actorId(),
                ipAddress: $this->actorIp(),
            );
        }

        // Dedicated status_changed event row (in addition to the generic updated rows).
        if ($newStatusValue !== null) {
            AuditLogger::log(
                auditable: $purchaseRequest,
                event: 'status_changed',
                field: 'status',
                oldValue: $oldStatusValue,
                newValue: $newStatusValue,
                userId: $this->actorId(),
                ipAddress: $this->actorIp(),
            );
        }
    }
}
