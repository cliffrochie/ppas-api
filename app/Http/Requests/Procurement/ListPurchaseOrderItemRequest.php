<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

final class ListPurchaseOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'purchase_order_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_orders,id'],
            'pr_item_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_request_items,id'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,purchase_order_id,pr_item_id,item_description,quantity,unit_of_measure,unit_cost,total_cost,created_at,updated_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
