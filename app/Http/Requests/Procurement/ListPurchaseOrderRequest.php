<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

final class ListPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'in:draft,for_signature,supplier_acceptance,delivery_inspection,completed'],
            'prepared_by_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'supplier_id' => ['sometimes', 'nullable', 'integer', 'exists:suppliers,id'],
            'purchase_request_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_requests,id'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:id,po_number,purchase_request_id,prepared_by_id,supplier_id,supplier_name,delivery_date,total_amount,status,created_at,updated_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }
}
