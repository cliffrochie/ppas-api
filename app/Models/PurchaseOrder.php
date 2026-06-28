<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'purchase_request_id',
    'prepared_by_id',
    'supplier_id',
    'po_number',
    'supplier_name',
    'supplier_address',
    'delivery_terms',
    'payment_terms',
    'delivery_date',
    'total_amount',
    'status',
    'signed_po_path',
])]
class PurchaseOrder extends Model
{
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'total_amount'  => 'decimal:2',
        ];
    }
}
