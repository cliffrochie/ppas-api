<?php

declare(strict_types=1);

namespace App\Http\Requests\Resolution;

use App\Models\BacResolution;
use Illuminate\Foundation\Http\FormRequest;

final class StoreBacResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', BacResolution::class);
    }

    public function rules(): array
    {
        return [
            'resolution_number'        => ['required', 'string', 'max:100'],
            'abstract_of_quotation_id' => ['required', 'exists:abstracts_of_quotation,id', 'unique:bac_resolutions,abstract_of_quotation_id'],
            'prepared_by_id'           => ['required', 'exists:users,id'],
            // file_path is derived server-side from the uploaded file — never accepted from the client.
            'file'                     => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'issued_at'                => ['nullable', 'date'],
        ];
    }
}
