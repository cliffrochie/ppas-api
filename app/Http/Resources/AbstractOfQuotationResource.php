<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AbstractOfQuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'rfq_id'               => $this->rfq_id,
            'prepared_by_id'       => $this->prepared_by_id,
            'recommended_supplier' => $this->recommended_supplier,
            'recommended_amount'   => $this->recommended_amount,
            'status'               => $this->status,
            // file_path intentionally excluded — served via authorized download route
            'approved_at'          => $this->approved_at,
            'prepared_by'          => new UserResource($this->whenLoaded('preparedBy')),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
