<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AbstractOfQuotation extends Model
{
    protected $table = 'abstracts_of_quotation';

    protected $fillable = [
        'rfq_id',
        'prepared_by_id',
        'recommended_supplier',
        'recommended_amount',
        'status',
        'file_path',
        'approved_at',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function bacResolution(): HasOne
    {
        return $this->hasOne(BacResolution::class);
    }

    protected function casts(): array
    {
        return [
            'recommended_amount' => 'decimal:2',
            'approved_at'        => 'datetime',
        ];
    }
}
