<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\PrAttachment;
use Illuminate\Foundation\Http\FormRequest;

final class StorePrAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PrAttachment::class);
    }

    public function rules(): array
    {
        return [
            'purchase_request_id' => ['required', 'exists:purchase_requests,id'],
            'type'                => ['required', 'in:app_ppmp,signed_pr,rfq,bac_resolution,noa,other'],
            // uploader_id, file_name, file_path, file_size, mime_type, uploaded_at are derived
            // server-side from the uploaded file — never accepted from the client.
            'file'                => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ];
    }
}
