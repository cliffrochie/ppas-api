<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DashboardRequest extends FormRequest
{
    /**
     * All authenticated users can access the dashboard regardless of role.
     * The auth:sanctum middleware on the route handles authentication.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year'      => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'office_id' => ['sometimes', 'nullable', 'integer', 'exists:offices,id'],
        ];
    }
}
