<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class StockAlertService
{
    public function isLow(?StockLevel $stock, Product $product): bool
    {
        if (! $stock) {
            return true;
        }

        return $stock->quantity < $product->min_stock_threshold;
    }

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
