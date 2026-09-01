<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Adds a `search` query scope that LIKE-matches a term across the columns
 * listed in the consuming model's `$searchable` property.
 */
trait Searchable
{
    /**
     * Apply a case-insensitive LIKE match for $term across the model's
     * $searchable columns. A blank term or empty column list is a no-op.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        $columns = $this->searchable ?? [];

        if ($term === '' || $columns === []) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term, $columns): void {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$term}%");
            }
        });
    }
}
