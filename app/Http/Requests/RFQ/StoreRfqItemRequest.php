<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use App\Models\RfqItem;
use Illuminate\Foundation\Http\FormRequest;

final class StoreRfqItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', RfqItem::class);
    }

    public function rules(): array
    {
        return [
            'rfq_id'           => ['required', 'exists:rfqs,id'],
            'pr_item_id'       => ['required', 'exists:purchase_request_items,id'],
            'item_description' => ['required', 'string'],
            'unit_of_measure'  => ['required', 'string', 'max:50'],
            'quantity'         => ['required', 'numeric', 'min:0'],
        ];
    }
}
