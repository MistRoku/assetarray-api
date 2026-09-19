<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

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
