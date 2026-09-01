<?php

declare(strict_types=1);

namespace App\Http\Requests\Monitoring;

use Illuminate\Foundation\Http\FormRequest;

final class ListAuditLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'auditable_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'auditable_id' => ['sometimes', 'nullable', 'integer'],
            'event' => ['sometimes', 'nullable', 'string', 'max:50'],
            'field' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ip_address' => ['sometimes', 'nullable', 'string', 'max:45'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,user_id,auditable_type,auditable_id,event,field,ip_address,created_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
