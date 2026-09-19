<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function create(User $user): bool
    {
        return $user->isManagerOrAbove();
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->isManagerOrAbove();
    }
}
