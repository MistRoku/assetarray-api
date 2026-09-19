<?php

namespace App\Listeners;

use App\Events\TransferRequested;
use App\Services\NotificationService;
use App\Services\StockAlertService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Notify the destination side that a transfer awaits approval.
 *
 * Queued (ShouldQueue). Recipient routing is shared with the low-stock
 * listener via StockAlertService::recipients() — super-admins plus the
 * destination branch's manager — instead of a second hand-rolled query.
 */
class SendTransferRequestNotification implements ShouldQueue
{
    public function __construct(
        private readonly StockAlertService $stockAlertService,
        private readonly NotificationService $notificationService
    ) {}

    public function handle(TransferRequested $event): void
    {
        // Refresh relations for message content; the event may carry a
        // transfer serialised without them.
        $transfer = $event->transfer->load(['product:id,name,sku', 'toBranch:id,name']);

        $recipients = $this->stockAlertService->recipients($transfer->product, $transfer->to_branch_id);

        // Truthiness (not nullsafe): the branch can be deleted at runtime
        // even though the relation types as non-null.
        $branchName = $transfer->toBranch ? $transfer->toBranch->name : 'unknown branch';

        foreach ($recipients as $user) {
            $this->notificationService->createForUser(
                user: $user,
                type: 'transfer_requested',
                title: 'Transfer request pending approval',
                body: sprintf(
                    'Transfer %s requests %d units of %s to %s.',
                    $transfer->transfer_code,
                    $transfer->quantity,
                    $transfer->product->name,
                    $branchName
                ),
                data: [
                    'transfer_id' => $transfer->id,
                    'transfer_code' => $transfer->transfer_code,
                ]
            );
        }
    }
}
