<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RfqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'rfq_number'          => $this->rfq_number,
            'purchase_request_id' => $this->purchase_request_id,
            'prepared_by_id'      => $this->prepared_by_id,
            'deadline'            => $this->deadline,
            'status'              => $this->status,
            // file_path intentionally excluded — served via authorized download route
            'prepared_by'         => new UserResource($this->whenLoaded('preparedBy')),
            'items'               => RfqItemResource::collection($this->whenLoaded('items')),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
