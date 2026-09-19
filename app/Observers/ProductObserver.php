<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductPriceHistory;

/**
 * Records price history automatically on every product update.
 *
 * Only fires when cost or selling price actually changed (isDirty guard), so
 * name/stock edits don't spam the history table. Partial changes record
 * only the changed side — the other pair stays null. Registered in
 * AppServiceProvider (the ONLY registration — a second one, e.g.
 * #[ObservedBy], would journal every change twice).
 */
class ProductObserver
{
    public function updating(Product $product): void
    {
        if (! $product->isDirty(['cost_price', 'selling_price'])) {
            return;
        }

        ProductPriceHistory::create([
            'product_id' => $product->id,
            'old_cost_price' => $product->isDirty('cost_price') ? $product->getOriginal('cost_price') : null,
            'new_cost_price' => $product->isDirty('cost_price') ? $product->cost_price : null,
            'old_selling_price' => $product->isDirty('selling_price') ? $product->getOriginal('selling_price') : null,
            'new_selling_price' => $product->isDirty('selling_price') ? $product->selling_price : null,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);
    }
}
