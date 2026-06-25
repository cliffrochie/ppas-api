<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PrAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'purchase_request_id' => $this->purchase_request_id,
            'uploader_id'         => $this->uploader_id,
            'type'                => $this->type,
            'file_name'           => $this->file_name,
            'file_size'           => $this->file_size,
            'mime_type'           => $this->mime_type,
            'uploaded_at'         => $this->uploaded_at,
            // file_path intentionally excluded — served via authorized download route only.
            // Consumers use download_url which is authenticated and role-gated.
            'download_url'        => route('pr_attachments.download', $this->id),
            'uploader'            => new UserResource($this->whenLoaded('uploader')),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
