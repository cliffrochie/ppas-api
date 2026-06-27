<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Supplier $supplier */
        $supplier = $this->route('supplier');

        return $this->user()->can('update', $supplier);
    }

    public function rules(): array
    {
        /** @var Supplier $supplier */
        $supplier = $this->route('supplier');

        return [
            'name'             => ['sometimes', 'string', 'max:255'],
            'tin_number'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'category_id'      => ['sometimes', 'nullable', 'exists:categories,id'],
            'website'          => ['sometimes', 'nullable', 'url', 'max:255'],
            'tags'             => ['sometimes', 'nullable', 'array'],
            'tags.*'           => ['string', 'max:100'],
            // Ignore the current supplier's own email in the unique constraint.
            'email'            => ['sometimes', 'email', 'max:255', Rule::unique('suppliers', 'email')->ignore($supplier->id)],
            'logo'             => ['sometimes', 'nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,svg,webp'],
            'contact_person'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone'            => ['sometimes', 'nullable', 'string', 'max:50'],
            'address_street'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_city'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_province' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_zip'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'is_active'        => ['sometimes', 'boolean'],
        ];
    }
}
