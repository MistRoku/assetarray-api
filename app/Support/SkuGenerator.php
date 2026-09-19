<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Generates collision-checked product SKUs.
 *
 * Used by Product::booted() when no SKU is supplied. Uniqueness is
 * best-effort (pre-check) + enforced (DB UNIQUE) — see make().
 */
final class SkuGenerator
{
    private const MAX_ATTEMPTS = 10;

    /**
     * Generate a unique product SKU.
     *
     * Format: PRD-XXXXXXXX
     *
     * Note: the exists() check is not atomic — concurrent inserts can still
     * collide. The `sku` column is UNIQUE, so callers must be prepared to
     * catch the query exception and retry. This method caps attempts so a
     * saturated key-space fails fast instead of looping forever.
     */
    public static function make(string $prefix = 'PRD'): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $sku = $prefix.'-'.Str::upper(Str::random(8));

            if (! self::exists($sku)) {
                return $sku;
            }
        }

        throw new RuntimeException('Unable to generate a unique SKU after '.self::MAX_ATTEMPTS.' attempts.');
    }

    /**
     * Check for an existing SKU, including trashed rows when the model
     * is soft-deletable, so deleted SKUs are never re-issued.
     */
    private static function exists(string $sku): bool
    {
        $query = Product::where('sku', $sku);

        // Include trashed rows when the model is soft-deletable so a
        // deleted SKU is never re-issued (restores would violate UNIQUE).
        if (method_exists(Product::class, 'withTrashed')) {
            $query = Product::withTrashed()->where('sku', $sku);
        }

        return $query->exists();
    }
}
