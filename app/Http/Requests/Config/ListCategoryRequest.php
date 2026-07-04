<?php

declare(strict_types=1);

namespace App\Http\Requests\Config;

use Illuminate\Foundation\Http\FormRequest;

final class ListCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
