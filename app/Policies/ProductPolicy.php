<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Product authorization. Catalogue writes and bulk CSV import are
 * manager-level: staff get read/count access only.
 */
class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->isManagerOrAbove();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isManagerOrAbove();
    }

    public function import(User $user): bool
    {
        return $user->isManagerOrAbove();
    }
}
