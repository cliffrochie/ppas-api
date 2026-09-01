<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BacResolution extends Model
{
    use Searchable;

    protected $fillable = [
        'resolution_number',
        'abstract_of_quotation_id',
        'prepared_by_id',
        'file_path',
        'issued_at',
    ];

    /** @var list<string> */
    protected array $searchable = ['resolution_number'];

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
