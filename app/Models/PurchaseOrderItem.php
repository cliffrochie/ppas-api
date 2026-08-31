<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'pr_item_id',
        'item_description',
        'unit_of_measure',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class, 'pr_item_id');
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
