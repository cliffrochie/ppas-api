<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use Illuminate\Foundation\Http\FormRequest;

final class ListAbstractOfQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rfq_id' => ['sometimes', 'nullable', 'integer', 'exists:rfqs,id'],
            'prepared_by_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'nullable', 'string', 'in:draft,approved'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,rfq_id,prepared_by_id,recommended_supplier,recommended_amount,status,approved_at,created_at,updated_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
