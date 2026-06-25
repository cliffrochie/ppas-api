<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purchase_request_id',
    'actor_id',
    'from_status',
    'to_status',
    'remarks',
    'alobs_number',
    'acted_at',
])]
class PrStatusHistory extends Model
{
    // Append-only audit log — no updated_at column.
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'acted_at'   => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
