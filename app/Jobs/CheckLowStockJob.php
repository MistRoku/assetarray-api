<?php

namespace App\Jobs;

use App\Events\StockBelowThreshold;
use App\Models\Product;
use App\Models\StockLevel;
use App\Services\StockAlertService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Verify a suspected low-stock situation and escalate it as an event.
 *
 * Dispatched by InventoryService::adjust() with the post-write state, but a
 * queued job runs LATER — stock may have recovered (or the product been
 * deleted) by then. So handle() re-reads everything and stays silent unless
 * the shortage is still real. The low-stock rule itself lives in
 * StockAlertService::isLow(), shared with any future callers.
 */
class CheckLowStockJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
        public int $branchId
    ) {}

    public function handle(StockAlertService $stockAlertService): void
    {
        // The serialised product may be stale or gone — work from fresh rows.
        $product = Product::find($this->product->id);

        if (! $product) {
            return;
        }

        $stock = StockLevel::query()
            ->where('product_id', $product->id)
            ->where('branch_id', $this->branchId)
            ->first();

        if ($stockAlertService->isLow($stock, $product)) {
            // Truthiness (not nullsafe): Larastan types first() as non-null,
            // but a concurrent delete can still null it — stay fail-safe.
            $quantity = $stock ? $stock->quantity : 0;

            event(new StockBelowThreshold($product, $this->branchId, $quantity));
        }
    }
}
