<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequestItem extends Model
{
    use Searchable;

    protected $fillable = [
        'purchase_request_id',
        'item_description',
        'specifications',
        'unit_of_measure',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    /** @var list<string> */
    protected array $searchable = ['item_description', 'unit_of_measure'];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'pr_item_id');
    }

    public function rfqItems(): HasMany
    {
        return $this->hasMany(RfqItem::class, 'pr_item_id');
    }

    protected function casts(): array
    {
        return [
            'quantity'   => 'decimal:2',
            'unit_cost'  => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }
}
