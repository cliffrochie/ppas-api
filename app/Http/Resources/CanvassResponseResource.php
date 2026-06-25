<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CanvassResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'rfq_id'        => $this->rfq_id,
            'rfq_item_id'   => $this->rfq_item_id,
            'supplier_name' => $this->supplier_name,
            'unit_price'    => $this->unit_price,
            'total_price'   => $this->total_price,
            'notes'         => $this->notes,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
