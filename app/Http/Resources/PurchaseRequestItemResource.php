<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseRequestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'item_description'    => $this->item_description,
            'specifications'      => $this->specifications,
            'unit_of_measure'     => $this->unit_of_measure,
            'quantity'            => $this->quantity,
            'unit_cost'           => $this->unit_cost,
            'total_cost'          => $this->total_cost,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
