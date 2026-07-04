<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

final class ListPurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'in:draft,submitted,under_review,returned,for_budget_approval,disapproved,budget_approved,forwarded_to_ppu,pr_prepared,pr_approved,rfq_prepared,canvassing,abstract_prepared,bac_resolution_noa,po_prepared,completed'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'requesting_office_id' => ['sometimes', 'nullable', 'integer', 'exists:offices,id'],
            'requires_philgeps' => ['sometimes', 'boolean'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
