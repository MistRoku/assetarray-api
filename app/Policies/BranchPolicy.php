<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

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
