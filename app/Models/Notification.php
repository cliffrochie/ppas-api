<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use Searchable;

    protected $fillable = [
        'user_id',
        'purchase_request_id',
        'type',
        'title',
        'message',
        'is_read',
        'read_at',
    ];

    /** @var list<string> */
    protected array $searchable = ['title', 'message'];

    // Immutable once created — no updated_at column.
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    protected function casts(): array
    {
        return [
            'is_read'    => 'boolean',
            'read_at'    => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
