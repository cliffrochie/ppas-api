<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NoticeOfAwardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'noa_number' => $this->noa_number,
            'bac_resolution_id' => $this->bac_resolution_id,
            'awarded_supplier' => $this->awarded_supplier,
            'awarded_amount' => $this->awarded_amount,
            // file_path intentionally excluded — served via authorized download route only.
            // Consumers use download_url which is authenticated and role-gated.
            // Always non-null: file_path is a required field on this model.
            'download_url' => route('notices_of_award.download', $this->id),
            'issued_at' => $this->issued_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
