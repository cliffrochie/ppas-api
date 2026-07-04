<?php

declare(strict_types=1);

namespace App\Http\Requests\RFQ;

use App\Models\Rfq;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRfqRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Rfq $rfq */
        $rfq = $this->route('rfq');

        return $this->user()->can('update', $rfq);
    }

    public function rules(): array
    {
        return [
            // rfq_number is system-generated — never accepted from input
            'prepared_by_id' => ['sometimes', 'required', 'exists:users,id'],
            'deadline'       => ['nullable', 'date'],
            'status'         => ['sometimes', 'in:draft,for_signature,signed,canvassing,closed'],
            // file_path is derived server-side from the uploaded file — never accepted from the client.
            'file'           => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ];
    }
}
