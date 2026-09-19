<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

/**
 * Purchase order authorization. The whole lifecycle (create/send/receive/
 * cancel) is managerial — POs commit company money, so staff are read-only
 * outside their own branch's view.
 */
class PurchaseOrderPolicy
{
    /**
     * Super-admins bypass every check below. Returning null (not false)
     * falls through to the specific ability method.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /** Anyone authenticated may list purchase orders. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Managers see all; others only their own branch's orders. */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isManagerOrAbove() || (int) $user->branch_id === (int) $purchaseOrder->branch_id;
    }

    /** Raising a PO commits spend — managers and up. */
    public function create(User $user): bool
    {
        return $user->isManagerOrAbove();
    }

    /** Sending puts the order in front of the supplier. */
    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isManagerOrAbove();
    }

    /** Booking receipts moves real stock — managers and up. */
    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isManagerOrAbove();
    }

    /**
     * Cancellation stops future receipts; already-received stock stays on
     * the shelves (see PurchaseOrderService::cancel()).
     */
    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isManagerOrAbove();
    }
}
