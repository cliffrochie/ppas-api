<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'tin_number'            => $this->tin_number,
            'category_id'           => $this->category_id,
            'website'               => $this->website,
            'tags'                  => $this->tags,
            'contact_person'        => $this->contact_person,
            'email'                 => $this->email,
            'phone'                 => $this->phone,
            'address_street'        => $this->address_street,
            'address_city'          => $this->address_city,
            'address_province'      => $this->address_province,
            'address_zip'           => $this->address_zip,
            'on_time_delivery_rate' => $this->on_time_delivery_rate,
            'defect_rate'           => $this->defect_rate,
            'is_active'             => $this->is_active,
            // logo_path intentionally excluded — served via authorized download route only.
            // Consumers use logo_url which is authenticated and role-gated.
            'logo_url'              => $this->logo_path !== null
                ? route('suppliers.download_logo', $this->id)
                : null,
            'category'              => new CategoryResource($this->whenLoaded('category')),
            'documents'             => SupplierDocumentResource::collection($this->whenLoaded('documents')),
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
