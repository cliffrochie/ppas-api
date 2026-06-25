<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PrStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'actor_id'            => $this->actor_id,
            'from_status'         => $this->from_status,
            'to_status'           => $this->to_status,
            'remarks'             => $this->remarks,
            'alobs_number'        => $this->alobs_number,
            'acted_at'            => $this->acted_at,
            'actor'               => new UserResource($this->whenLoaded('actor')),
            'created_at'          => $this->created_at,
        ];
    }
}
