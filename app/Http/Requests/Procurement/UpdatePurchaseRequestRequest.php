<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\PurchaseRequest;
use App\Support\PurchaseRequestTransitions;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PurchaseRequest $purchaseRequest */
        $purchaseRequest = $this->route('purchase_request');

        // Base policy check (owns the resource, correct role for general writes).
        if (! $this->user()->can('update', $purchaseRequest)) {
            return false;
        }

        // If the request is changing status, enforce the per-transition role restriction.
        $newStatus = $this->input('status');
        if ($newStatus !== null) {
            $allowedRoles = PurchaseRequestTransitions::allowedRoles(
                $purchaseRequest->status,
                $newStatus,
            );

            // If the transition has a role restriction and the user's role is not in it, deny.
            if ($allowedRoles !== null && ! in_array($this->user()->role?->name, $allowedRoles, true)) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            // rf_number and pr_number are system-generated — never accepted from input
            'requester_id'         => ['sometimes', 'required', 'exists:users,id'],
            'requesting_office_id' => ['sometimes', 'required', 'exists:offices,id'],
            'category_id'          => ['nullable', 'exists:categories,id'],
            'purpose'              => ['sometimes', 'required', 'string'],
            'status'               => ['sometimes', 'in:draft,submitted,under_review,returned,for_budget_approval,disapproved,budget_approved,forwarded_to_ppu,pr_prepared,pr_approved,rfq_prepared,canvassing,abstract_prepared,bac_resolution_noa,po_prepared,completed'],
            // alobs_number is required when the Budget Officer approves (status → budget_approved).
            'alobs_number'         => ['required_if:status,budget_approved', 'nullable', 'string', 'max:100'],
            // remarks are required when returning or disapproving so the actor must provide a reason.
            'remarks'              => ['required_if:status,returned', 'required_if:status,disapproved', 'nullable', 'string'],
            'total_amount'         => ['nullable', 'numeric', 'min:0'],
            'submitted_at'         => ['nullable', 'date'],
        ];
    }
}
