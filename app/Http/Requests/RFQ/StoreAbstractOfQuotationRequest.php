<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use App\Models\AbstractOfQuotation;
use Illuminate\Foundation\Http\FormRequest;

final class StoreAbstractOfQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AbstractOfQuotation::class);
    }

    public function rules(): array
    {
        return [
            'rfq_id'               => ['required', 'exists:rfqs,id', 'unique:abstracts_of_quotation,rfq_id'],
            'prepared_by_id'       => ['required', 'exists:users,id'],
            'recommended_supplier' => ['nullable', 'string', 'max:255'],
            'recommended_amount'   => ['nullable', 'numeric', 'min:0'],
            'status'               => ['sometimes', 'in:draft,approved'],
            // file_path is derived server-side from the uploaded file — never accepted from the client.
            'file'                 => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'approved_at'          => ['nullable', 'date'],
        ];
    }
}
