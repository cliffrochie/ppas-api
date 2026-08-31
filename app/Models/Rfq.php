<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rfq extends Model
{
    protected $table = 'rfqs';

    protected $fillable = [
        'purchase_request_id',
        'prepared_by_id',
        'rfq_number',
        'deadline',
        'status',
        'file_path',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class);
    }

    public function canvassResponses(): HasMany
    {
        return $this->hasMany(CanvassResponse::class);
    }

    public function abstractOfQuotation(): HasOne
    {
        return $this->hasOne(AbstractOfQuotation::class);
    }

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
        ];
    }
}
