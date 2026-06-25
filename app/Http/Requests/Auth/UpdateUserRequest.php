<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->route('user');

        return $this->user()->can('update', $user);
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'first_name'     => ['sometimes', 'required', 'string', 'max:100'],
            'middle_name'    => ['nullable', 'string', 'max:100'],
            'last_name'      => ['sometimes', 'required', 'string', 'max:100'],
            'extension_name' => ['nullable', 'string', 'max:20'],
            'email'          => ['sometimes', 'required', 'email', "unique:users,email,{$userId}"],
            'password'       => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'role_id'        => ['sometimes', 'required', 'exists:roles,id'],
            'office_id'      => ['nullable', 'exists:offices,id'],
            'is_active'      => ['boolean'],
        ];
    }
}
