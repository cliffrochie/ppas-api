<?php

declare(strict_types=1);

namespace App\Http\Requests\Resolution;

use Illuminate\Foundation\Http\FormRequest;

final class ListBacResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'abstract_of_quotation_id' => ['sometimes', 'nullable', 'integer', 'exists:abstracts_of_quotation,id'],
            'prepared_by_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
