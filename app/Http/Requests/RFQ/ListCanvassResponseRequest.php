<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use Illuminate\Foundation\Http\FormRequest;

final class ListCanvassResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rfq_id' => ['sometimes', 'nullable', 'integer', 'exists:rfqs,id'],
            'rfq_item_id' => ['sometimes', 'nullable', 'integer', 'exists:rfq_items,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,rfq_id,rfq_item_id,supplier_name,unit_price,total_price,created_at,updated_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
