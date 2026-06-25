<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseRequest::class);
    }

    public function rules(): array
    {
        return [
            // rf_number and pr_number are system-generated — never accepted from input
            'requester_id'         => ['required', 'exists:users,id'],
            'requesting_office_id' => ['required', 'exists:offices,id'],
            'category_id'          => ['nullable', 'exists:categories,id'],
            'purpose'              => ['required', 'string'],
            'status'               => ['sometimes', 'in:draft,submitted,under_review,returned,for_budget_approval,disapproved,budget_approved,forwarded_to_ppu,pr_prepared,pr_approved,rfq_prepared,canvassing,abstract_prepared,bac_resolution_noa,po_prepared,completed'],
            'alobs_number'         => ['nullable', 'string', 'max:100'],
            'total_amount'         => ['nullable', 'numeric', 'min:0'],
            'submitted_at'         => ['nullable', 'date'],
        ];
    }
}
