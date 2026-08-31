<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeOfAward extends Model
{
    protected $table = 'notices_of_award';

    protected $fillable = [
        'noa_number',
        'bac_resolution_id',
        'awarded_supplier',
        'awarded_amount',
        'file_path',
        'issued_at',
    ];

    public function bacResolution(): BelongsTo
    {
        return $this->belongsTo(BacResolution::class);
    }

    protected function casts(): array
    {
        return [
            'awarded_amount' => 'decimal:2',
            'issued_at'      => 'date',
        ];
    }
}
