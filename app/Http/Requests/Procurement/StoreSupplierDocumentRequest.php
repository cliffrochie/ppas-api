<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\SupplierDocument;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SupplierDocument::class);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            // uploader_id, file_name, file_path, file_size, mime_type, uploaded_at
            // are derived server-side from the uploaded file — never accepted from the client.
            'file'        => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ];
    }
}
