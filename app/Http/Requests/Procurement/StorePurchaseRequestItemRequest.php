<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\PurchaseRequestItem;
use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseRequestItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseRequestItem::class);
    }

    public function rules(): array
    {
        return [
            'purchase_request_id' => ['required', 'exists:purchase_requests,id'],
            'item_description'    => ['required', 'string'],
            'specifications'      => ['nullable', 'string'],
            'unit_of_measure'     => ['required', 'string', 'max:50'],
            'quantity'            => ['required', 'numeric', 'min:0'],
            'unit_cost'           => ['required', 'numeric', 'min:0'],
            'total_cost'          => ['required', 'numeric', 'min:0'],
        ];
    }
}
