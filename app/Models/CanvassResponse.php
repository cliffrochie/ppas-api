<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanvassResponse extends Model
{
    protected $fillable = [
        'rfq_id',
        'rfq_item_id',
        'supplier_name',
        'unit_price',
        'total_price',
        'notes',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class);
    }

    protected function casts(): array
    {
        return [
            'unit_price'  => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }
}
