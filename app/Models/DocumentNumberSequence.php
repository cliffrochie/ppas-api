<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'prefix',
    'year',
    'month',
    'last_sequence',
])]
class DocumentNumberSequence extends Model
{
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'last_sequence' => 'integer',
        ];
    }
}
