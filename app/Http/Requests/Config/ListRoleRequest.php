<?php

declare(strict_types=1);

namespace App\Http\Requests\Config;

use Illuminate\Foundation\Http\FormRequest;

final class ListRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,name,description,created_at,updated_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
