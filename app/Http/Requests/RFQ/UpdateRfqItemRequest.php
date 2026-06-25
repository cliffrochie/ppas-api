<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use App\Models\RfqItem;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRfqItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var RfqItem $item */
        $item = $this->route('rfq_item');

        return $this->user()->can('update', $item);
    }

    public function rules(): array
    {
        return [
            'rfq_id'           => ['sometimes', 'required', 'exists:rfqs,id'],
            'pr_item_id'       => ['sometimes', 'required', 'exists:purchase_request_items,id'],
            'item_description' => ['sometimes', 'required', 'string'],
            'unit_of_measure'  => ['sometimes', 'required', 'string', 'max:50'],
            'quantity'         => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
