<?php

namespace App\Support;

use App\Models\StockTake;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Generates collision-checked stock take references.
 *
 * The YYYYMMDD prefix scopes references by day; uniqueness comes from the
 * random suffix + DB UNIQUE, not the date.
 */
final class StockTakeReferenceGenerator
{
    private const MAX_ATTEMPTS = 10;

    /**
     * Format: STK-YYYYMMDD-XXXX
     *
     * Note: the exists() check is not atomic — concurrent inserts can still
     * collide. The `reference` column is UNIQUE, so callers must be prepared
     * to catch the query exception and retry. Attempts are capped so a
     * saturated key-space fails fast instead of looping forever.
     */
    public static function make(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $reference = 'STK-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));

            if (! self::exists($reference)) {
                return $reference;
            }
        }

        throw new RuntimeException('Unable to generate a unique stock take reference after '.self::MAX_ATTEMPTS.' attempts.');
    }

    /**
     * Check for an existing reference, including trashed rows when the
     * model is soft-deletable, so deleted references are never re-issued.
     */
    private static function exists(string $reference): bool
    {
        $query = StockTake::where('reference', $reference);

        if (method_exists(StockTake::class, 'withTrashed')) {
            $query = StockTake::withTrashed()->where('reference', $reference);
        }

        return $query->exists();
    }
}
