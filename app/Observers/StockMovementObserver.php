<?php

namespace App\Observers;

use App\Models\StockMovement;
use RuntimeException;

/**
 * Enforces the append-only ledger: movements are never edited or removed.
 * Corrections are new adjustment rows (see InventoryService). Registered
 * via #[ObservedBy] on the StockMovement model.
 */
class StockMovementObserver
{
    public function updating(StockMovement $stockMovement): void
    {
        throw new RuntimeException('Stock movements are immutable.');
    }

    public function deleting(StockMovement $stockMovement): void
    {
        throw new RuntimeException('Stock movements are immutable.');
    }
}
