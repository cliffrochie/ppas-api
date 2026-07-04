<?php

declare(strict_types=1);

namespace App\Http\Requests\Resolution;

use App\Models\NoticeOfAward;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateNoticeOfAwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var NoticeOfAward $noticeOfAward */
        $noticeOfAward = $this->route('notice_of_award');

        return $this->user()->can('update', $noticeOfAward);
    }

    public function rules(): array
    {
        return [
            'noa_number'       => ['sometimes', 'required', 'string', 'max:100'],
            'awarded_supplier' => ['sometimes', 'required', 'string', 'max:255'],
            'awarded_amount'   => ['sometimes', 'required', 'numeric', 'min:0'],
            // file_path is derived server-side from the uploaded file — never accepted from the client.
            'file'             => ['sometimes', 'required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'issued_at'        => ['nullable', 'date'],
        ];
    }
}
