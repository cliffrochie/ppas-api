<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use App\Models\AbstractOfQuotation;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAbstractOfQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AbstractOfQuotation $abstractOfQuotation */
        $abstractOfQuotation = $this->route('abstract_of_quotation');

        return $this->user()->can('update', $abstractOfQuotation);
    }

    public function rules(): array
    {
        return [
            'prepared_by_id'       => ['sometimes', 'required', 'exists:users,id'],
            'recommended_supplier' => ['nullable', 'string', 'max:255'],
            'recommended_amount'   => ['nullable', 'numeric', 'min:0'],
            'status'               => ['sometimes', 'in:draft,approved'],
            'file_path'            => ['nullable', 'string', 'max:500'],
            'approved_at'          => ['nullable', 'date'],
        ];
    }
}
