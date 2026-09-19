<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Product authorization. Reads are open; all writes plus the sensitive
 * extras (bulk import, price history with cost data) are manager-level.
 */
class ProductPolicy
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

    /** Anyone authenticated may list products. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Anyone authenticated may view a product. */
    public function view(User $user, Product $product): bool
    {
        return true;
    }

    /** Catalogue writes are manager-level and up. */
    public function create(User $user): bool
    {
        return $user->isManagerOrAbove();
    }

    /** Catalogue writes are manager-level and up. */
    public function update(User $user, Product $product): bool
    {
        return $user->isManagerOrAbove();
    }

    /** Soft-delete; the SKU stays reserved via withTrashed checks. */
    public function delete(User $user, Product $product): bool
    {
        return $user->isManagerOrAbove();
    }

    /** Bulk import can create hundreds of rows — managers only. */
    public function import(User $user): bool
    {
        return $user->isManagerOrAbove();
    }

    /** Price history exposes cost prices — managers only, not staff. */
    public function priceHistory(User $user, Product $product): bool
    {
        return $user->isManagerOrAbove();
    }
}
