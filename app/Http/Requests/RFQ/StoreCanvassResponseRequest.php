<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use App\Models\CanvassResponse;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCanvassResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CanvassResponse::class);
    }

    public function rules(): array
    {
        return [
            'rfq_id'        => ['required', 'exists:rfqs,id'],
            'rfq_item_id'   => ['required', 'exists:rfq_items,id'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'unit_price'    => ['required', 'numeric', 'min:0'],
            'total_price'   => ['required', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string'],
        ];
    }
}
