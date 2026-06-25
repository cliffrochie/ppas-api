<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'user_id'         => $this->user_id,
            'auditable_type'  => $this->auditable_type,
            'auditable_id'    => $this->auditable_id,
            'event'           => $this->event,
            'field'           => $this->field,
            'old_value'       => $this->old_value,
            'new_value'       => $this->new_value,
            'ip_address'      => $this->ip_address,
            'user'            => new UserResource($this->whenLoaded('user')),
            'created_at'      => $this->created_at,
        ];
    }
}
