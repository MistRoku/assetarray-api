<?php

namespace App\Events;

use App\Models\StockTransfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StockTransfer $transfer,
    ) {}
}
