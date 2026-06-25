<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'po_number'           => $this->po_number,
            'purchase_request_id' => $this->purchase_request_id,
            'prepared_by_id'      => $this->prepared_by_id,
            'supplier_name'       => $this->supplier_name,
            'supplier_address'    => $this->supplier_address,
            'delivery_terms'      => $this->delivery_terms,
            'payment_terms'       => $this->payment_terms,
            'delivery_date'       => $this->delivery_date,
            'total_amount'        => $this->total_amount,
            'status'              => $this->status,
            // signed_po_path intentionally excluded — served via authorized download route
            'prepared_by'         => new UserResource($this->whenLoaded('preparedBy')),
            'items'               => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
