<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

/**
 * Supplier authorization. Day-to-day writes are managerial; deletion is
 * super-admin only since it rewrites history links (POs null theirs, but
 * preferred-supplier links on products go dangling).
 */
class SupplierPolicy
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

    /** Anyone authenticated may list suppliers. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Anyone authenticated may view a supplier. */
    public function view(User $user, Supplier $supplier): bool
    {
        return true;
    }

    /** Supplier onboarding is managerial work. */
    public function create(User $user): bool
    {
        return $user->isManagerOrAbove();
    }

    /** Supplier edits are managerial work. */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->isManagerOrAbove();
    }

    /** Deletion is destructive — super-admins only. */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->isSuperAdmin();
    }
}
