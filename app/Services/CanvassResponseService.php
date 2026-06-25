<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CanvassResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CanvassResponseService
{
    public function list(): LengthAwarePaginator
    {
        return CanvassResponse::latest()->paginate(15);
    }

    public function store(array $validated): CanvassResponse
    {
        return DB::transaction(fn (): CanvassResponse => CanvassResponse::create($validated));
    }

    public function update(CanvassResponse $response, array $validated): CanvassResponse
    {
        return DB::transaction(function () use ($response, $validated): CanvassResponse {
            $response->update($validated);

            return $response->refresh();
        });
    }

    public function destroy(CanvassResponse $response): void
    {
        DB::transaction(function () use ($response): void { $response->delete(); });
    }
}
