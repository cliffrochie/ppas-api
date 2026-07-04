<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CategoryService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Category::query()
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when(array_key_exists('is_active', $filters), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->orderBy('name')
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
