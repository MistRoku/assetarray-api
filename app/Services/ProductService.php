<?php

namespace App\Services;

use App\Jobs\ProcessProductCsvImportJob;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class ProductService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $branchId = $filters['branch_id'] ?? null;

        $query = Product::query()
            ->with(['category:id,name', 'supplier:id,name'])
            ->search($filters['search'] ?? null)
            ->when($filters['category_id'] ?? null, fn (Builder $q, $categoryId): Builder => $q->where('category_id', $categoryId))
            ->when($branchId, function (Builder $q) use ($branchId): void {
                $q->whereHas('stockLevels', fn (Builder $stockQuery): Builder => $stockQuery->where('branch_id', $branchId));
            });

        if ($branchId) {
            $query->with(['stockLevels' => fn (Builder $q): Builder => $q->where('branch_id', $branchId)]);
        }

        $stockStatus = $filters['stock_status'] ?? null;

        if ($stockStatus === 'in_stock') {
            $query->whereHas('stockLevels', function (Builder $q) use ($branchId): void {
                $q->when($branchId, fn (Builder $qq, $id): Builder => $qq->where('branch_id', $id))
                    ->where('quantity', '>', 0);
            });
        }

        if ($stockStatus === 'low_stock') {
            $query->whereHas('stockLevels', function (Builder $q) use ($branchId): void {
                $q->when($branchId, fn (Builder $qq, $id): Builder => $qq->where('branch_id', $id))
                    ->where('quantity', '>', 0)
                    ->whereRaw('stock_levels.quantity < products.min_stock_threshold');
            });
        }

        if ($stockStatus === 'out_of_stock') {
            $query->whereDoesntHave('stockLevels', function (Builder $q) use ($branchId): void {
                $q->when($branchId, fn (Builder $qq, $id): Builder => $qq->where('branch_id', $id))
                    ->where('quantity', '>', 0);
            });
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);

            $this->auditLogService->log(
                action: 'created',
                entityType: Product::class,
                entityId: $product->id,
                newValues: $product->toArray()
            );

            return $product->load(['category', 'supplier']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $oldValues = $product->only(array_keys($data));

            $product->update($data);

            $this->auditLogService->log(
                action: 'updated',
                entityType: Product::class,
                entityId: $product->id,
                oldValues: $oldValues,
                newValues: $product->only(array_keys($data))
            );

            return $product->fresh(['category', 'supplier']);
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->delete();

            $this->auditLogService->log(
                action: 'deleted',
                entityType: Product::class,
                entityId: $product->id,
                oldValues: $product->only(['name', 'sku', 'is_active'])
            );
        });
    }

    public function priceHistory(Product $product)
    {
        return $product->priceHistories()
            ->with('changedBy:id,name,email')
            ->latest('changed_at')
            ->get();
    }

    public function importCsv(UploadedFile $file): string
    {
        $path = $file->store('product-imports', 'local');

        ProcessProductCsvImportJob::dispatch($path, auth()->id());

        return $path;
    }
}
