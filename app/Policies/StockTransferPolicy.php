<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

/**
 * Transfer authorization. Branch ids are cast to int before comparison:
 * Eloquent usually hydrates them as ints, but a strict === against a
 * string-hydrated id would fail closed and strand a transfer, so normalize.
 *
 * Semantics: the DESTINATION branch owns approval/receipt (they accept the
 * stock); creation is any manager's job. Super-admins bypass all of this.
 */
class StockTransferPolicy
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

    /** Anyone authenticated may list transfers. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Managers see everything; anyone else only transfers touching their
     * own branch (either side).
     */
    public function view(User $user, StockTransfer $transfer): bool
    {
        return $user->isManagerOrAbove()
            || (int) $user->branch_id === (int) $transfer->from_branch_id
            || (int) $user->branch_id === (int) $transfer->to_branch_id;
    }

    /** Any manager may request a transfer. Stock is re-checked at approval. */
    public function create(User $user): bool
    {
        return $user->isManagerOrAbove();
    }

    /** Only the destination branch's manager accepts incoming stock. */
    public function approve(User $user, StockTransfer $transfer): bool
    {
        return $user->isBranchManager() && (int) $user->branch_id === (int) $transfer->to_branch_id;
    }

    /** Only the destination branch's manager rejects incoming stock. */
    public function reject(User $user, StockTransfer $transfer): bool
    {
        return $user->isBranchManager() && (int) $user->branch_id === (int) $transfer->to_branch_id;
    }

    /** Only the destination branch's manager books the receipt. */
    public function receive(User $user, StockTransfer $transfer): bool
    {
        return $user->isBranchManager() && (int) $user->branch_id === (int) $transfer->to_branch_id;
    }
}
