<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'rf_number'            => $this->rf_number,
            'pr_number'            => $this->pr_number,
            'requester_id'         => $this->requester_id,
            'requesting_office_id' => $this->requesting_office_id,
            'category_id'          => $this->category_id,
            'purpose'              => $this->purpose,
            'status'               => $this->status,
            'alobs_number'         => $this->alobs_number,
            'total_amount'         => $this->total_amount,
            'submitted_at'         => $this->submitted_at,
            'requires_philgeps'    => $this->requires_philgeps,
            'requester'            => new UserResource($this->whenLoaded('requester')),
            'requesting_office'    => new OfficeResource($this->whenLoaded('requestingOffice')),
            'category'             => new CategoryResource($this->whenLoaded('category')),
            'items'                => PurchaseRequestItemResource::collection($this->whenLoaded('items')),
            'attachments'          => PrAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
