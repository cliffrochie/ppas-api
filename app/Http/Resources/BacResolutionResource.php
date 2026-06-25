<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class BacResolutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'resolution_number'         => $this->resolution_number,
            'abstract_of_quotation_id'  => $this->abstract_of_quotation_id,
            'prepared_by_id'            => $this->prepared_by_id,
            // file_path intentionally excluded — served via authorized download route
            'issued_at'                 => $this->issued_at,
            'prepared_by'               => new UserResource($this->whenLoaded('preparedBy')),
            'created_at'                => $this->created_at,
            'updated_at'                => $this->updated_at,
        ];
    }
}
