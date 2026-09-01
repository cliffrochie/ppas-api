<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CategoryService
{
    private const ALLOWED_SORTS = [
        'id', 'name', 'code', 'description', 'is_active', 'created_at', 'updated_at',
    ];

    public function list(array $filters = []): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? null;
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        return Category::query()
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when(
                $sortBy && in_array($sortBy, self::ALLOWED_SORTS, true),
                fn ($q) => $q->orderBy($sortBy, $sortOrder),
                fn ($q) => $q->orderBy('name')
            )
            ->paginate(15);
    }

    public function store(array $validated): Category
    {
        return DB::transaction(fn (): Category => Category::create($validated));
    }

    public function update(Category $category, array $validated): Category
    {
        return DB::transaction(function () use ($category, $validated): Category {
            $category->update($validated);

            return $category->refresh();
        });
    }

    public function destroy(Category $category): void
    {
        DB::transaction(function () use ($category): void {
            $category->delete();
        });
    }
}
