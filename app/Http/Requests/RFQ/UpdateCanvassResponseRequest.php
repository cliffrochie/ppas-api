<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use App\Models\CanvassResponse;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCanvassResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CanvassResponse $canvassResponse */
        $canvassResponse = $this->route('canvass_response');

        return $this->user()->can('update', $canvassResponse);
    }

    public function rules(): array
    {
        return [
            'rfq_id'        => ['sometimes', 'required', 'exists:rfqs,id'],
            'rfq_item_id'   => ['sometimes', 'required', 'exists:rfq_items,id'],
            'supplier_name' => ['sometimes', 'required', 'string', 'max:255'],
            'unit_price'    => ['sometimes', 'required', 'numeric', 'min:0'],
            'total_price'   => ['sometimes', 'required', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string'],
        ];
    }
}
