<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use Illuminate\Foundation\Http\FormRequest;

final class ListRfqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'in:draft,for_signature,signed,canvassing,closed'],
            'prepared_by_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'purchase_request_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_requests,id'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,rfq_number,purchase_request_id,prepared_by_id,deadline,status,created_at,updated_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
