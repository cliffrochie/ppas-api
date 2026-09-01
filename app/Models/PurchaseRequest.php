<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequest extends Model
{
    use Searchable;

    protected $fillable = [
        'requester_id',
        'requesting_office_id',
        'category_id',
        'purpose',
        'status',
        'alobs_number',
        'total_amount',
        'submitted_at',
        'requires_philgeps',
    ];

    /** @var list<string> */
    protected array $searchable = ['purpose', 'pr_number', 'rf_number', 'alobs_number'];

    /**
     * Automatically compute `requires_philgeps` before every save.
     * Requests with a total amount of ₱50,000 or more require mandatory PhilGEPS posting
     * per the system reminders shown on the New Request form.
     */
    protected static function booted(): void
    {
        static::saving(function (self $purchaseRequest): void {
            $purchaseRequest->requires_philgeps = ((float) ($purchaseRequest->total_amount ?? 0)) >= 50000.0;
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function requestingOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'requesting_office_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PrAttachment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PrStatusHistory::class);
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    public function rfq(): HasOne
    {
        return $this->hasOne(Rfq::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    protected function casts(): array
    {
        return [
            'total_amount'     => 'decimal:2',
            'submitted_at'     => 'datetime',
            'requires_philgeps' => 'boolean',
        ];
    }
}
