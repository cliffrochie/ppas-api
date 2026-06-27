<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'supplier_id'  => $this->supplier_id,
            'uploader_id'  => $this->uploader_id,
            'file_name'    => $this->file_name,
            'file_size'    => $this->file_size,
            'mime_type'    => $this->mime_type,
            'uploaded_at'  => $this->uploaded_at,
            // file_path intentionally excluded — served via authorized download route only.
            'download_url' => route('supplier_documents.download', $this->id),
            'uploader'     => new UserResource($this->whenLoaded('uploader')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
