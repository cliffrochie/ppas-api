<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'tin_number',
    'category_id',
    'website',
    'tags',
    'logo_path',
    'contact_person',
    'email',
    'phone',
    'address_street',
    'address_city',
    'address_province',
    'address_zip',
    'on_time_delivery_rate',
    'defect_rate',
    'is_active',
])]
class Supplier extends Model
{
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    protected function casts(): array
    {
        return [
            'tags'                  => 'array',
            'on_time_delivery_rate' => 'decimal:2',
            'defect_rate'           => 'decimal:2',
            'is_active'             => 'boolean',
        ];
    }
}
