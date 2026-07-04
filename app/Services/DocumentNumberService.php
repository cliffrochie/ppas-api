<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DocumentNumberSequence;
use Illuminate\Support\Facades\DB;

final class DocumentNumberService
{
    /**
     * Generate the next {PREFIX}-{YYYY}-{MM}-{SEQ} document number for the
     * given prefix, scoped per calendar month. Concurrency-safe: the counter
     * row is locked (lockForUpdate) inside its own transaction, so concurrent
     * callers serialize on the same (prefix, year, month) scope instead of
     * racing on a max()+1 query.
     */
    public function generate(string $prefix): string
    {
        $year = now()->year;
        $month = now()->month;

        return DB::transaction(function () use ($prefix, $year, $month): string {
            $sequence = DocumentNumberSequence::where('prefix', $prefix)
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->first() ?? DocumentNumberSequence::create([
                    'prefix' => $prefix,
                    'year' => $year,
                    'month' => $month,
                    'last_sequence' => 0,
                ]);

            $sequence->increment('last_sequence');

            return sprintf('%s-%04d-%02d-%03d', $prefix, $year, $month, $sequence->last_sequence);
        });
    }
}
