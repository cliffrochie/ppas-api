<?php

declare(strict_types=1);

namespace App\Http\Requests\Resolution;

use App\Models\NoticeOfAward;
use Illuminate\Foundation\Http\FormRequest;

final class StoreNoticeOfAwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', NoticeOfAward::class);
    }

    public function rules(): array
    {
        return [
            'noa_number'        => ['required', 'string', 'max:100'],
            'bac_resolution_id' => ['required', 'exists:bac_resolutions,id', 'unique:notices_of_award,bac_resolution_id'],
            'awarded_supplier'  => ['required', 'string', 'max:255'],
            'awarded_amount'    => ['required', 'numeric', 'min:0'],
            // file_path is derived server-side from the uploaded file — never accepted from the client.
            'file'              => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'issued_at'         => ['nullable', 'date'],
        ];
    }
}
