<?php

declare(strict_types=1);

namespace App\Http\Requests\Config;

use App\Models\Office;
use Illuminate\Foundation\Http\FormRequest;

final class StoreOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Office::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
