<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\PurchaseOrderItem;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePurchaseOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PurchaseOrderItem $item */
        $item = $this->route('purchase_order_item');

        return $this->user()->can('update', $item);
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => ['sometimes', 'required', 'exists:purchase_orders,id'],
            'pr_item_id'        => ['nullable', 'exists:purchase_request_items,id'],
            'item_description'  => ['sometimes', 'required', 'string'],
            'unit_of_measure'   => ['sometimes', 'required', 'string', 'max:50'],
            'quantity'          => ['sometimes', 'required', 'numeric', 'min:0'],
            'unit_cost'         => ['sometimes', 'required', 'numeric', 'min:0'],
            'total_cost'        => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
