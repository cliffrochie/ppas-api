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
            'rfq_id' => ['sometimes', 'nullable', 'integer', 'exists:rfqs,id'],
            'pr_item_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_request_items,id'],
        ];
    }
}
