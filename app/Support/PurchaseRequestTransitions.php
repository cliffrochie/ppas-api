<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Encapsulates the role-permission rules for purchase request status transitions.
 * Both the Form Request (authorize) and tests consume this single source of truth.
 */
final class PurchaseRequestTransitions
{
    /**
     * Maps each transition (from → to) to the role(s) allowed to trigger it.
     * Keys are "from:to" strings. Values are arrays of permitted role names.
     *
     * Transitions not listed here are either structurally invalid (checked in the
     * service) or have no additional role restriction beyond the base policy.
     */
    public const ROLE_MAP = [
        'draft:submitted'                        => ['requester'],
        'submitted:under_review'                 => ['procurement_officer'],
        'submitted:returned'                     => ['procurement_officer'],
        'under_review:for_budget_approval'       => ['procurement_officer'],
        'under_review:returned'                  => ['procurement_officer'],
        'returned:submitted'                     => ['requester'],
        'for_budget_approval:budget_approved'    => ['budget_officer'],
        'for_budget_approval:disapproved'        => ['budget_officer'],
        'budget_approved:forwarded_to_ppu'       => ['budget_officer'],
        // forwarded_to_ppu onwards — all handled by procurement_officer
        'forwarded_to_ppu:pr_prepared'           => ['procurement_officer'],
        'pr_prepared:pr_approved'                => ['procurement_officer'],
        'pr_approved:rfq_prepared'               => ['procurement_officer'],
        'rfq_prepared:canvassing'                => ['procurement_officer'],
        'canvassing:abstract_prepared'           => ['procurement_officer'],
        'abstract_prepared:bac_resolution_noa'   => ['procurement_officer'],
        'bac_resolution_noa:po_prepared'         => ['procurement_officer'],
        'po_prepared:completed'                  => ['procurement_officer'],
    ];

    /**
     * Return the roles permitted to make the given transition, or null if the
     * transition has no specific role restriction (falls back to base policy).
     *
     * @return string[]|null
     */
    public static function allowedRoles(string $from, string $to): ?array
    {
        return self::ROLE_MAP["{$from}:{$to}"] ?? null;
    }
}
