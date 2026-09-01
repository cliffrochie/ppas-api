<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use Illuminate\Foundation\Http\FormRequest;

final class ListRfqItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'rfq_id' => ['sometimes', 'nullable', 'integer', 'exists:rfqs,id'],
            'pr_item_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_request_items,id'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,rfq_id,pr_item_id,item_description,quantity,unit_of_measure,created_at,updated_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
