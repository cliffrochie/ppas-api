<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidStatusTransitionException;
use App\Models\PurchaseRequest;
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
        'draft'               => ['submitted'],
        'submitted'           => ['under_review', 'returned'],
        'under_review'        => ['for_budget_approval', 'returned'],
        'returned'            => ['submitted'],
        'for_budget_approval' => ['budget_approved', 'disapproved'],
        'disapproved'         => [],
        'budget_approved'     => ['forwarded_to_ppu'],
        'forwarded_to_ppu'    => ['pr_prepared'],
        'pr_prepared'         => ['pr_approved'],
        'pr_approved'         => ['rfq_prepared'],
        'rfq_prepared'        => ['canvassing'],
        'canvassing'          => ['abstract_prepared'],
        'abstract_prepared'   => ['bac_resolution_noa'],
        'bac_resolution_noa'  => ['po_prepared'],
        'po_prepared'         => ['completed'],
        'completed'           => [],
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

    public function __construct(private readonly Request $request) {}

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

        if (! in_array($next, $allowed, true)) {
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

    public function list(): LengthAwarePaginator
    {
        return PurchaseRequest::with(['requester', 'requestingOffice', 'category'])
            ->latest()
            ->paginate(15);
    }

    public function show(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        return $purchaseRequest->load(['requester', 'requestingOffice', 'category', 'items', 'attachments']);
    }

    public function store(array $validated): PurchaseRequest
    {
        return DB::transaction(function () use ($validated): PurchaseRequest {
            $year = now()->year;

            // Lock the highest RF number for this year to prevent concurrent duplicates.
            $last = PurchaseRequest::whereYear('created_at', $year)
                ->whereNotNull('rf_number')
                ->lockForUpdate()
                ->max('rf_number');

            $next = $last !== null ? ((int) substr($last, -5)) + 1 : 1;
            $rfNumber = sprintf('RF-%d-%05d', $year, $next);

            // rf_number is not in fillable — set directly after creation.
            $purchaseRequest = PurchaseRequest::create($validated);
            $purchaseRequest->rf_number = $rfNumber;
            $purchaseRequest->save();

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
            // Capture originals before any write so the audit delta is accurate.
            $originalValues = collect(self::AUDITED_FIELDS)
                ->mapWithKeys(fn (string $field) => [$field => $purchaseRequest->getRawOriginal($field)])
                ->all();

            if (isset($validated['status']) && $validated['status'] === 'submitted') {
                // Stamp submitted_at on first submission.
                $validated['submitted_at'] = now();

                // Generate PR number when transitioning to 'submitted' for the first time.
                if ($purchaseRequest->pr_number === null) {
                    $year = now()->year;

                    $last = PurchaseRequest::whereYear('created_at', $year)
                        ->whereNotNull('pr_number')
                        ->lockForUpdate()
                        ->max('pr_number');

                    $next = $last !== null ? ((int) substr($last, -5)) + 1 : 1;
                    // pr_number is not in fillable — forceFill writes it alongside validated fields.
                    $validated['pr_number'] = sprintf('PR-%d-%05d', $year, $next);
                }
            }

            // Use forceFill so pr_number (not in #[Fillable]) can be written in one shot.
            $purchaseRequest->forceFill($validated)->save();
            $purchaseRequest->refresh()->load(['requester', 'requestingOffice', 'category']);

            // Audit: log changed fields and the dedicated status_changed event if applicable.
            $this->auditUpdated($purchaseRequest, $originalValues, $validated);

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

        if (! empty($updatedChanges)) {
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
