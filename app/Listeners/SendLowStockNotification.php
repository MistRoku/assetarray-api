<?php

namespace App\Listeners;

use App\Events\StockBelowThreshold;
use App\Services\NotificationService;
use App\Services\StockAlertService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Fan out a low-stock inbox notification to everyone who should act.
 *
 * Queued (ShouldQueue) so a slow inbox never delays the stock write that
 * triggered it. Recipients come from StockAlertService — the same rule the
 * transfer listener reuses, so alert routing stays in one place.
 */
class SendLowStockNotification implements ShouldQueue
{
    public function __construct(
        private readonly StockAlertService $stockAlertService,
        private readonly NotificationService $notificationService
    ) {}

    public function handle(StockBelowThreshold $event): void
    {
        $recipients = $this->stockAlertService->recipients($event->product, $event->branchId);

        foreach ($recipients as $user) {
            $this->notificationService->createForUser(
                user: $user,
                type: 'low_stock',
                title: 'Low stock alert',
                body: sprintf(
                    'Product %s (%s) is low at branch ID %d. Current quantity: %d.',
                    $event->product->name,
                    $event->product->sku,
                    $event->branchId,
                    $event->quantity
                ),
                data: [
                    'product_id' => $event->product->id,
                    'branch_id' => $event->branchId,
                    'quantity' => $event->quantity,
                ]
            );
        }
    }
}
