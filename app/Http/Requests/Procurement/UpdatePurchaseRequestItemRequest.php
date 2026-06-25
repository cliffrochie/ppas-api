<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\PurchaseRequestItem;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePurchaseRequestItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PurchaseRequestItem $item */
        $item = $this->route('purchase_request_item');

        return $this->user()->can('update', $item);
    }

    public function rules(): array
    {
        return [
            'purchase_request_id' => ['sometimes', 'required', 'exists:purchase_requests,id'],
            'item_description'    => ['sometimes', 'required', 'string'],
            'specifications'      => ['nullable', 'string'],
            'unit_of_measure'     => ['sometimes', 'required', 'string', 'max:50'],
            'quantity'            => ['sometimes', 'required', 'numeric', 'min:0'],
            'unit_cost'           => ['sometimes', 'required', 'numeric', 'min:0'],
            'total_cost'          => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
