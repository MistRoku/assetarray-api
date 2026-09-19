<?php

namespace App\Support;

use App\Models\StockTransfer;
use Illuminate\Support\Str;

final class TransferCodeGenerator
{
    /**
     * Format: TRF-YYYYMMDD-XXXX
     */
    public static function make(): string
    {
        do {
            $code = 'TRF-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));
        } while (StockTransfer::where('transfer_code', $code)->exists());

        return $code;
    }
}
