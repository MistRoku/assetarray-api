<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Decides "is this low stock?" and "who needs to know?".
 *
 * Pure logic (no writes) backing CheckLowStockJob: a missing StockLevel
 * counts as low (zero on hand), and alerts go to super-admins plus the
 * owning branch's manager — never to other branches' managers.
 */
final class StockAlertService
{
    /** Missing level means zero on hand, which is always low. */
    public function isLow(?StockLevel $stock, Product $product): bool
    {
        if (! $stock) {
            return true;
        }

        return $stock->quantity < $product->min_stock_threshold;
    }

    /**
     * Alert recipients for a product at a branch: active super-admins and
     * the active manager of that branch only. Inactive users never qualify.
     *
     * @return Collection<int, User>
     */
    public function recipients(Product $product, int $branchId): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($branchId): void {
                $query->where('role', User::ROLE_SUPER_ADMIN)
                    ->orWhere(function (Builder $q) use ($branchId): void {
                        $q->where('role', User::ROLE_BRANCH_MANAGER)
                            ->where('branch_id', $branchId);
                    });
            })
            ->get();
    }
}
