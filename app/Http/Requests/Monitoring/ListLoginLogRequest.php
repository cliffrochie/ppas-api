<?php

declare(strict_types=1);

namespace App\Http\Requests\Monitoring;

use Illuminate\Foundation\Http\FormRequest;

final class ListLoginLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'in:success,failed,locked_out'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,user_id,email,ip_address,status,created_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
