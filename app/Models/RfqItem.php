<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RfqItem extends Model
{
    protected $fillable = [
        'rfq_id',
        'pr_item_id',
        'item_description',
        'unit_of_measure',
        'quantity',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function purchaseRequestItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestItem::class, 'pr_item_id');
    }

    public function canvassResponses(): HasMany
    {
        return $this->hasMany(CanvassResponse::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }
}
