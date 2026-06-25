<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\PurchaseOrderItem;
use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseOrderItem::class);
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'pr_item_id'        => ['nullable', 'exists:purchase_request_items,id'],
            'item_description'  => ['required', 'string'],
            'unit_of_measure'   => ['required', 'string', 'max:50'],
            'quantity'          => ['required', 'numeric', 'min:0'],
            'unit_cost'         => ['required', 'numeric', 'min:0'],
            'total_cost'        => ['required', 'numeric', 'min:0'],
        ];
    }
}
