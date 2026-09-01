<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Searchable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Searchable;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'email',
        'password',
        'role_id',
        'office_id',
        'is_active',
    ];

    /** @var list<string> */
    protected array $searchable = ['first_name', 'last_name', 'email'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Computed full name for display on printed documents.
     * Handles optional middle name and extension name gracefully.
     */
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $parts = array_filter([
                    $this->first_name,
                    $this->middle_name,
                    $this->last_name,
                    $this->extension_name,
                ]);

                return implode(' ', $parts);
            },
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class, 'requester_id');
    }

    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(PrAttachment::class, 'uploader_id');
    }

    public function statusHistoryActions(): HasMany
    {
        return $this->hasMany(PrStatusHistory::class, 'actor_id');
    }

    public function preparedPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'prepared_by_id');
    }

    public function preparedRfqs(): HasMany
    {
        return $this->hasMany(Rfq::class, 'prepared_by_id');
    }

    public function preparedAbstracts(): HasMany
    {
        return $this->hasMany(AbstractOfQuotation::class, 'prepared_by_id');
    }

    public function preparedBacResolutions(): HasMany
    {
        return $this->hasMany(BacResolution::class, 'prepared_by_id');
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
