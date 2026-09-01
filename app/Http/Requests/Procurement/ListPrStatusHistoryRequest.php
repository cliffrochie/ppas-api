<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

final class ListPrStatusHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_request_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_requests,id'],
            'actor_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'from_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'to_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,purchase_request_id,actor_id,from_status,to_status,alobs_number,acted_at,created_at,updated_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
