<?php

declare(strict_types=1);

namespace App\Http\Requests\Config;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Role $role */
        $role = $this->route('role');

        return $this->user()->can('update', $role);
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name'        => ['sometimes', 'required', 'string', 'max:100', "unique:roles,name,{$roleId}"],
            'description' => ['nullable', 'string'],
        ];
    }
}
