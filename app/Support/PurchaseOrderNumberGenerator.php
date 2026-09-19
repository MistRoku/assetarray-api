<?php

namespace App\Support;

use App\Models\PurchaseOrder;
use Illuminate\Support\Str;

final class PurchaseOrderNumberGenerator
{
    /**
     * Format: PO-YYYYMM-XXXXX
     */
    public static function make(): string
    {
        do {
            $number = 'PO-'.now()->format('Ym').'-'.Str::upper(Str::random(5));
        } while (PurchaseOrder::where('po_number', $number)->exists());

        return $number;
    }
}
