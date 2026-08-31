<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Supplier::class);
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'tin_number'       => ['nullable', 'string', 'max:100'],
            'category_id'      => ['nullable', 'exists:categories,id'],
            'website'          => ['nullable', 'url', 'max:255'],
            'tags'             => ['nullable', 'array'],
            'tags.*'           => ['string', 'max:100'],
            // logo is an optional file upload — stored on the private disk.
            // logo_path is derived server-side and never accepted from the client.
            // svg excluded — it can carry inline scripts (stored-XSS vector on upload).
            'logo'             => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp'],
            'contact_person'   => ['nullable', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:suppliers,email'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'address_street'   => ['nullable', 'string', 'max:255'],
            'address_city'     => ['nullable', 'string', 'max:255'],
            'address_province' => ['nullable', 'string', 'max:255'],
            'address_zip'      => ['nullable', 'string', 'max:20'],
            'is_active'        => ['sometimes', 'boolean'],
        ];
    }
}
