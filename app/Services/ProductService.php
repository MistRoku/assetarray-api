<?php

namespace App\Services;

use App\Jobs\ProcessProductCsvImportJob;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Product catalogue: filtered listing, CRUD and CSV import dispatch.
 *
 * stock_status filters (in_stock / low_stock / out_of_stock) are evaluated
 * against live stock_levels, optionally scoped to one branch. The low_stock
 * comparison uses whereRaw against products.min_stock_threshold — keep the
 * table prefix if the join strategy changes.
 */
final class ProductService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * Paginated catalogue. Pass branch_id to scope stock filters and eager
     * loads to that branch only; otherwise stock across all branches counts.
     */
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
            $query->with(['stockLevels' => fn (Relation $q): Relation => $q->where('branch_id', $branchId)]);
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

    /**
     * Create a product (SKU auto-generated when omitted) and audit it.
     * Returns the product with category/supplier eager-loaded for the response.
     */
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

    /** Update a product; audits only the keys actually changed. */
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

    /** Soft-delete a product. The SKU stays reserved (withTrashed check). */
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

    /**
     * Price-change history, newest first. Ordered by changed_at (not
     * created_at) — that column is the event timestamp for this table.
     */
    public function priceHistory(Product $product)
    {
        return $product->priceHistories()
            ->with('changedBy:id,name,email')
            ->latest('changed_at')
            ->get();
    }

    /**
     * Store the uploaded CSV and queue it for background processing.
     * Returns the storage path; the job (not this request) parses rows, so
     * large files don't block the response. Tracked to the uploading user.
     */
    public function importCsv(UploadedFile $file): string
    {
        $path = $file->store('product-imports', 'local');

        ProcessProductCsvImportJob::dispatch($path, auth()->id());

        return $path;
    }
}
