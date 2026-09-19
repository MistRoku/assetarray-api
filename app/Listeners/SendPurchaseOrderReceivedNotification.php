<?php

namespace App\Listeners;

use App\Events\PurchaseOrderReceived;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Tell whoever raised the PO that goods arrived (partial or complete).
 *
 * Queued (ShouldQueue). Silently skips system-raised orders with no
 * requester, and tolerates a deleted supplier — the receipt itself already
 * succeeded, a notification must never fail it.
 */
class SendPurchaseOrderReceivedNotification implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(PurchaseOrderReceived $event): void
    {
        $po = $event->purchaseOrder->load(['orderedBy:id,name,email', 'supplier:id,name']);

        if (! $po->orderedBy) {
            return;
        }

        // Truthiness (not nullsafe): suppliers are soft-deletable with
        // nullOnDelete, so this can be null at runtime.
        $supplierName = $po->supplier ? $po->supplier->name : 'unknown supplier';

        $this->notificationService->createForUser(
            user: $po->orderedBy,
            type: 'purchase_order_received',
            title: 'Purchase order update',
            body: sprintf(
                'Purchase order %s from %s is now %s.',
                $po->po_number,
                $supplierName,
                $po->status
            ),
            data: [
                'purchase_order_id' => $po->id,
                'po_number' => $po->po_number,
                'status' => $po->status,
            ]
        );
    }
}
