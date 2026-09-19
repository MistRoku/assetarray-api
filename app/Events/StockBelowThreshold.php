<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a product's stock drops below its min threshold.
 *
 * Dispatched by CheckLowStockJob AFTER re-reading live stock, so listeners
 * never act on the stale quantity that triggered the job. The embedded
 * product is a notification snapshot (name/sku/id) — don't use it for writes.
 */
class StockBelowThreshold
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Product $product,
        public int $branchId,
        public int $quantity
    ) {}
}
