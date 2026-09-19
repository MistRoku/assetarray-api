<?php

namespace App\Events;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after each receiveGoods() batch (partial or full receipt).
 *
 * Listeners can check $purchaseOrder->status to distinguish partial vs
 * complete arrivals. Same transaction caveat as TransferRequested.
 */
class PurchaseOrderReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
    ) {}
}
