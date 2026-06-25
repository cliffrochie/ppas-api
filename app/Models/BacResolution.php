<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'resolution_number',
    'abstract_of_quotation_id',
    'prepared_by_id',
    'file_path',
    'issued_at',
])]
class BacResolution extends Model
{
    public function abstractOfQuotation(): BelongsTo
    {
        return $this->belongsTo(AbstractOfQuotation::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function noticeOfAward(): HasOne
    {
        return $this->hasOne(NoticeOfAward::class);
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
        ];
    }
}
