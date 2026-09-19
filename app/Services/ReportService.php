<?php

namespace App\Services;

use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReportService
{
    public function inventoryValuation(array $filters = []): Collection
    {
        return StockLevel::query()
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('branches', 'branches.id', '=', 'stock_levels.branch_id')
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('branches.id', $id))
            ->select([
                'branches.id as branch_id',
                'branches.name as branch',
                'products.sku',
                'products.name as product',
                'stock_levels.quantity',
                'products.cost_price',
                DB::raw('stock_levels.quantity * products.cost_price as valuation'),
            ])
            ->get();
    }

    public function lowStock(array $filters = []): Collection
    {
        return StockLevel::query()
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('stock_levels.branch_id', $id))
            ->whereColumn('stock_levels.quantity', '<', 'products.min_stock_threshold')
            ->select([
                'stock_levels.branch_id',
                'stock_levels.product_id',
                'stock_levels.quantity',
                'products.name',
                'products.sku',
                'products.min_stock_threshold',
            ])
            ->with(['branch:id,name', 'product:id,name,sku,min_stock_threshold'])
            ->get();
    }

    public function stockMovements(array $filters = []): Collection
    {
        return StockMovement::query()
            ->with(['product:id,name,sku', 'branch:id,name', 'createdBy:id,name'])
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('branch_id', $id))
            ->when($filters['product_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('product_id', $id))
            ->when($filters['movement_type'] ?? null, fn (Builder $q, $type): Builder => $q->where('movement_type', $type))
            ->when($filters['from'] ?? null, fn (Builder $q, $from): Builder => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to): Builder => $q->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->get();
    }

    public function productPerformance(array $filters = []): Collection
    {
        return StockMovement::query()
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.movement_type', StockMovement::TYPE_SALE)
            ->when($filters['from'] ?? null, fn (Builder $q, $from): Builder => $q->whereDate('stock_movements.created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to): Builder => $q->whereDate('stock_movements.created_at', '<=', $to))
            ->select([
                'products.id as product_id',
                'products.sku',
                'products.name',
                DB::raw('SUM(ABS(stock_movements.quantity)) as units_sold'),
                DB::raw('SUM(COALESCE(stock_movements.total_amount, ABS(stock_movements.quantity) * products.selling_price)) as revenue'),
            ])
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->get();
    }

    public function transferHistory(array $filters = []): Collection
    {
        return StockTransfer::query()
            ->with(['product:id,name,sku', 'fromBranch:id,name', 'toBranch:id,name', 'requestedBy:id,name', 'approvedBy:id,name'])
            ->when($filters['status'] ?? null, fn (Builder $q, $status): Builder => $q->where('status', $status))
            ->when($filters['from_branch_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('from_branch_id', $id))
            ->when($filters['to_branch_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('to_branch_id', $id))
            ->when($filters['from'] ?? null, fn (Builder $q, $from): Builder => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to): Builder => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->get();
    }

    public function exportData(string $type, array $filters = []): array
    {
        $data = match ($type) {
            'inventory-valuation' => $this->inventoryValuation($filters),
            'low-stock' => $this->lowStock($filters),
            'stock-movements' => $this->stockMovements($filters),
            'product-performance' => $this->productPerformance($filters),
            'transfers' => $this->transferHistory($filters),
            default => collect(),
        };

        return $data->map(fn ($row) => (array) $row)->all();
    }
}
