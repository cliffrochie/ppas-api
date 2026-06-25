<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\PrAttachment;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePrAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PrAttachment $prAttachment */
        $prAttachment = $this->route('pr_attachment');

        return $this->user()->can('update', $prAttachment);
    }

    public function rules(): array
    {
        return [
            'purchase_request_id' => ['sometimes', 'required', 'exists:purchase_requests,id'],
            'uploader_id'         => ['sometimes', 'required', 'exists:users,id'],
            'type'                => ['sometimes', 'required', 'in:app_ppmp,signed_pr,rfq,bac_resolution,noa,other'],
            'file_name'           => ['sometimes', 'required', 'string', 'max:255'],
            'file_path'           => ['sometimes', 'required', 'string', 'max:500'],
            'file_size'           => ['sometimes', 'required', 'integer', 'min:1'],
            'mime_type'           => ['sometimes', 'required', 'string', 'max:100'],
            'uploaded_at'         => ['sometimes', 'required', 'date'],
        ];
    }
}
