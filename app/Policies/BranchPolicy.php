<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

/**
 * Branch authorization. Auto-discovered by Laravel (Branch ↔ BranchPolicy).
 *
 * Managers can create/edit branches; only super-admins may (re)assign the
 * branch manager — otherwise a manager could hand their branch to anyone.
 */
class BranchPolicy
{
    public function create(User $user): bool
    {
        return $user->isManagerOrAbove();
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->isManagerOrAbove();
    }

    public function assignManager(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }
}
