<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use Searchable;

    protected $fillable = ['name', 'description'];

    /** @var list<string> */
    protected array $searchable = ['name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
