<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'pr_item_id'        => $this->pr_item_id,
            'item_description'  => $this->item_description,
            'unit_of_measure'   => $this->unit_of_measure,
            'quantity'          => $this->quantity,
            'unit_cost'         => $this->unit_cost,
            'total_cost'        => $this->total_cost,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
