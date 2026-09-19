<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Supplier CRUD. Mirrors BranchService: deactivate() flips is_active then
 * soft-deletes so order history keeps its supplier link.
 */
final class SupplierService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /** Paginated suppliers with name/email/contact search. */
    public function list(array $filters): LengthAwarePaginator
    {
        return Supplier::query()
            ->when($filters['search'] ?? null, function (Builder $q, string $search): void {
                $q->where(function (Builder $qq) use ($search): void {
                    $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%");
                });
            })
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    /** Create a supplier and audit it. */
    public function create(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            $supplier = Supplier::create($data);

            $this->auditLogService->log(
                action: 'created',
                entityType: Supplier::class,
                entityId: $supplier->id,
                newValues: $supplier->toArray()
            );

            return $supplier;
        });
    }

    /** Update a supplier; audits only the keys actually changed. */
    public function update(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $oldValues = $supplier->only(array_keys($data));

            $supplier->update($data);

            $this->auditLogService->log(
                action: 'updated',
                entityType: Supplier::class,
                entityId: $supplier->id,
                oldValues: $oldValues,
                newValues: $supplier->only(array_keys($data))
            );

            // refresh() (not fresh()): fresh() is nullable (?Supplier) while
            // this method promises Supplier.
            $supplier->refresh();

            return $supplier;
        });
    }

    /** Deactivate then soft-delete; order history keeps working. */
    public function deactivate(Supplier $supplier): void
    {
        DB::transaction(function () use ($supplier) {
            $supplier->update(['is_active' => false]);
            $supplier->delete();

            $this->auditLogService->log(
                action: 'deleted',
                entityType: Supplier::class,
                entityId: $supplier->id,
                oldValues: $supplier->only(['name', 'email', 'is_active'])
            );
        });
    }
}
