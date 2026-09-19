<?php

namespace App\Policies;

use App\Models\StockTake;
use App\Models\User;

/**
 * Stock take authorization. Counting is deliberately open to anyone on the
 * branch (staff do the walking); approval stays managerial. Super-admins
 * bypass via before().
 */
class StockTakePolicy
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

    /** Anyone authenticated may list stock takes. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Managers see all; others only their own branch's takes. */
    public function view(User $user, StockTake $stockTake): bool
    {
        return $user->isManagerOrAbove() || (int) $user->branch_id === (int) $stockTake->branch_id;
    }

    /** Opening a count session is managerial work. */
    public function create(User $user): bool
    {
        return $user->isManagerOrAbove();
    }

    /**
     * No role check on purpose — any user assigned to the branch may count.
     * Matches SubmitStockTakeCountsRequest (auth-only). Unassigned users
     * (null branch_id) can never equal a real branch id.
     */
    public function submitCounts(User $user, StockTake $stockTake): bool
    {
        return (int) $user->branch_id === (int) $stockTake->branch_id;
    }

    /** Only the branch's own manager writes counts into live stock. */
    public function approve(User $user, StockTake $stockTake): bool
    {
        return $user->isBranchManager() && (int) $user->branch_id === (int) $stockTake->branch_id;
    }
}
