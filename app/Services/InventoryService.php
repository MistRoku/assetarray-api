<?php

namespace App\Services;

use App\Jobs\CheckLowStockJob;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InventoryService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return StockLevel::query()
            ->with(['product:id,name,sku,min_stock_threshold', 'branch:id,name'])
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $branchId): Builder => $q->where('branch_id', $branchId))
            ->when($filters['product_id'] ?? null, fn (Builder $q, $productId): Builder => $q->where('product_id', $productId))
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $q->whereHas('product', function (Builder $productQuery) use ($search): void {
                    $productQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            // FIX: was `$pq->column(...)` — Builder has no column() method (fatal).
            ->when(isset($filters['low_stock']), function (Builder $q) use ($filters): void {
                if (filter_var($filters['low_stock'], FILTER_VALIDATE_BOOLEAN)) {
                    $q->whereHas('product', function (Builder $pq): void {
                        $pq->whereColumn('products.min_stock_threshold', '>', 'stock_levels.quantity');
                    });
                }
            })
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function adjust(array $data): StockLevel
    {
        return DB::transaction(function () use ($data) {
            $product = Product::findOrFail($data['product_id']);

            $stock = StockLevel::query()
                ->where('product_id', $product->id)
                ->where('branch_id', $data['branch_id'])
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = StockLevel::create([
                    'product_id' => $product->id,
                    'branch_id' => $data['branch_id'],
                    'quantity' => 0,
                ]);
            }

            $oldQuantity = $stock->quantity;
            $newQuantity = $oldQuantity + (int) $data['quantity'];

            $isCorrection = str_contains(mb_strtolower((string) $data['reason']), 'correction');

            if ($newQuantity < 0 && ! $isCorrection) {
                throw ValidationException::withMessages([
                    'quantity' => ['Adjustment would result in negative stock. Use a correction reason for overrides.'],
                ]);
            }

            $stock->update(['quantity' => $newQuantity]);

            StockMovement::create([
                'product_id' => $product->id,
                'branch_id' => $data['branch_id'],
                'movement_type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => $data['quantity'],
                'reference_type' => StockLevel::class,
                'reference_id' => $stock->id,
                'reason' => $data['reason'],
                'created_by' => auth()->id(),
            ]);

            if ($newQuantity < $product->min_stock_threshold) {
                CheckLowStockJob::dispatch($product, (int) $data['branch_id']);
            }

            $this->auditLogService->log(
                action: 'updated',
                entityType: StockLevel::class,
                entityId: $stock->id,
                oldValues: ['quantity' => $oldQuantity],
                newValues: ['quantity' => $newQuantity]
            );

            return $stock->fresh(['product:id,name,sku,min_stock_threshold', 'branch:id,name']);
        });
    }

    public function movements(array $filters): LengthAwarePaginator
    {
        return StockMovement::query()
            ->with([
                'product:id,name,sku',
                'branch:id,name',
                'createdBy:id,name,email',
            ])
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $branchId): Builder => $q->where('branch_id', $branchId))
            ->when($filters['product_id'] ?? null, fn (Builder $q, $productId): Builder => $q->where('product_id', $productId))
            ->when($filters['movement_type'] ?? null, fn (Builder $q, $type): Builder => $q->where('movement_type', $type))
            ->when($filters['from'] ?? null, fn (Builder $q, $from): Builder => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to): Builder => $q->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
