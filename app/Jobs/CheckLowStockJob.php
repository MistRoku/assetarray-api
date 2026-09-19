<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued follow-up after a stock write drops a product below its threshold.
 *
 * Dispatched by InventoryService::adjust(). Runs async so the HTTP response
 * isn't delayed by notification fan-out. Recipients come from
 * StockAlertService::recipients().
 */
class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly int $branchId,
    ) {}

    public function handle(): void
    {
        // TODO: implement low-stock notification logic.
    }
}
