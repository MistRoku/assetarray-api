<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

/**
 * Branch authorization. Auto-discovered by Laravel (Branch ↔ BranchPolicy).
 *
 * Branches are org structure, so ALL writes (create/update/delete/assign)
 * are super-admin only — a deliberate tightening: branch managers run their
 * branch, they don't reshape the org. Everyone can read.
 */
class BranchPolicy
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

    /** Anyone authenticated may list branches. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Anyone authenticated may view a branch. */
    public function view(User $user, Branch $branch): bool
    {
        return true;
    }

    /** Branches are created by super-admins only. */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /** Branches are edited by super-admins only. */
    public function update(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    /** Deactivation goes through BranchService::deactivate(), gate kept here. */
    public function delete(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Reassigning the manager is super-admin only — otherwise a manager
     * could hand their branch to anyone.
     */
    public function assignManager(User $user, Branch $branch): bool
    {
        return $user->isSuperAdmin();
    }
}
