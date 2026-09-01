<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use Searchable;

    protected $fillable = [
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
    ];

    /** @var list<string> */
    protected array $searchable = ['name', 'tin_number', 'email', 'contact_person'];

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
