<?php

namespace App\Support;

use App\Models\StockTake;
use Illuminate\Support\Str;

final class StockTakeReferenceGenerator
{
    /**
     * Format: STK-YYYYMMDD-XXXX
     */
    public static function make(): string
    {
        do {
            $reference = 'STK-'. now()->format('Ymd'). '-'. Str::upper(Str::random(4));
        } while (StockTake::where('reference', $reference)->exists());

        return $reference;
    }
}
