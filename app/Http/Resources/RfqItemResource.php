<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RfqItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'rfq_id'           => $this->rfq_id,
            'pr_item_id'       => $this->pr_item_id,
            'item_description' => $this->item_description,
            'unit_of_measure'  => $this->unit_of_measure,
            'quantity'         => $this->quantity,
            'canvass_responses' => CanvassResponseResource::collection($this->whenLoaded('canvassResponses')),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
