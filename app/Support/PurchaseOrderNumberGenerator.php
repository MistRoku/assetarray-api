<?php

namespace App\Support;

use App\Models\PurchaseOrder;
use Illuminate\Support\Str;
use RuntimeException;

final class PurchaseOrderNumberGenerator
{
    private const MAX_ATTEMPTS = 10;

    /**
     * Format: PO-YYYYMM-XXXXX
     *
     * Note: the exists() check is not atomic — concurrent inserts can still
     * collide. The `po_number` column is UNIQUE, so callers must be prepared
     * to catch the query exception and retry.
     */
    public static function make(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $number = 'PO-'.now()->format('Ym').'-'.Str::upper(Str::random(5));

            if (! self::exists($number)) {
                return $number;
            }
        }

        throw new RuntimeException('Unable to generate a unique purchase order number after '.self::MAX_ATTEMPTS.' attempts.');
    }

    private static function exists(string $number): bool
    {
        $query = PurchaseOrder::where('po_number', $number);

        if (method_exists(PurchaseOrder::class, 'withTrashed')) {
            $query = PurchaseOrder::withTrashed()->where('po_number', $number);
        }

        return $query->exists();
    }
}
