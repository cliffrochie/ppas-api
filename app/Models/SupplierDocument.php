<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDocument extends Model
{
    use Searchable;

    protected $fillable = [
        'supplier_id',
        'uploader_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_at',
    ];

    /** @var list<string> */
    protected array $searchable = ['file_name'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'file_size'   => 'integer',
        ];
    }
}
