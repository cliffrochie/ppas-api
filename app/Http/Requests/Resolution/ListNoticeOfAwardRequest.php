<?php

declare(strict_types=1);

namespace App\Http\Requests\Resolution;

use Illuminate\Foundation\Http\FormRequest;

final class ListNoticeOfAwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bac_resolution_id' => ['sometimes', 'nullable', 'integer', 'exists:bac_resolutions,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,noa_number,bac_resolution_id,awarded_supplier,awarded_amount,issued_at,created_at,updated_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
