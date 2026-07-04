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
            'purchase_order_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_orders,id'],
            'pr_item_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_request_items,id'],
        ];
    }
}
