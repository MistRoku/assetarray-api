<?php

namespace App\Support;

use App\Models\StockTransfer;
use Illuminate\Support\Str;
use RuntimeException;

final class TransferCodeGenerator
{
    private const MAX_ATTEMPTS = 10;

    /**
     * Format: TRF-YYYYMMDD-XXXX
     *
     * Note: the exists() check is not atomic — concurrent inserts can still
     * collide. The `transfer_code` column is UNIQUE, so callers must be
     * prepared to catch the query exception and retry.
     */
    public static function make(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $code = 'TRF-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));

            if (! self::exists($code)) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate a unique transfer code after '.self::MAX_ATTEMPTS.' attempts.');
    }

    private static function exists(string $code): bool
    {
        $query = StockTransfer::where('transfer_code', $code);

        if (method_exists(StockTransfer::class, 'withTrashed')) {
            $query = StockTransfer::withTrashed()->where('transfer_code', $code);
        }

        return $query->exists();
    }
}
