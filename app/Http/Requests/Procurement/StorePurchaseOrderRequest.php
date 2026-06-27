<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PurchaseOrder::class);
    }

    public function rules(): array
    {
        return [
            // po_number is system-generated — never accepted from input
            'purchase_request_id' => ['required', 'exists:purchase_requests,id', 'unique:purchase_orders,purchase_request_id'],
            'prepared_by_id'      => ['required', 'exists:users,id'],
            'supplier_name'       => ['nullable', 'string', 'max:255'],
            'supplier_address'    => ['nullable', 'string'],
            'delivery_terms'      => ['nullable', 'string'],
            'payment_terms'       => ['nullable', 'string'],
            'delivery_date'       => ['nullable', 'date'],
            'total_amount'        => ['nullable', 'numeric', 'min:0'],
            'status'              => ['sometimes', 'in:draft,for_signature,supplier_acceptance,delivery_inspection,completed'],
            'signed_po_path'      => ['nullable', 'string', 'max:500'],
        ];
    }
}
