<?php

declare(strict_types=1);

namespace App\Http\Requests\Config;

use App\Models\Office;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Office $office */
        $office = $this->route('office');

        return $this->user()->can('update', $office);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
