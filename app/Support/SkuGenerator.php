<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Str;

final class SkuGenerator
{
    /**
     * Generate a unique product SKU.
     *
     * Format: PRD-XXXXXXXX
     */
    public static function make(string $prefix = 'PRD'): string
    {
        do {
            $sku = $prefix.'-'.Str::upper(Str::random(8));
        } while (Product::withTrashed()->where('sku', $sku)->exists());

        return $sku;
    }
}
