<?php

namespace App\Events;

use App\Models\StockTransfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a stock transfer is requested (still pending).
 *
 * Hook for notifications to the source branch manager. Fired inside the
 * creating transaction — listeners must be queued if they do I/O.
 */
class TransferRequested
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly StockTransfer $transfer,
    ) {}
}
