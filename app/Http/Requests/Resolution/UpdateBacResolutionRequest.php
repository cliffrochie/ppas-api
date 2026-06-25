<?php

declare(strict_types=1);

namespace App\Http\Requests\Resolution;

use App\Models\BacResolution;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateBacResolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BacResolution $bacResolution */
        $bacResolution = $this->route('bac_resolution');

        return $this->user()->can('update', $bacResolution);
    }

    public function rules(): array
    {
        return [
            'resolution_number' => ['sometimes', 'required', 'string', 'max:100'],
            'prepared_by_id'    => ['sometimes', 'required', 'exists:users,id'],
            'file_path'         => ['sometimes', 'required', 'string', 'max:500'],
            'issued_at'         => ['nullable', 'date'],
        ];
    }
}
