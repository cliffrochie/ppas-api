<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,
            'purchase_request_id' => $this->purchase_request_id,
            'type'                => $this->type,
            'title'               => $this->title,
            'message'             => $this->message,
            'is_read'             => $this->is_read,
            'read_at'             => $this->read_at,
            'created_at'          => $this->created_at,
        ];
    }
}
