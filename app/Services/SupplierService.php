<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class SupplierService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

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

            return $supplier->fresh();
        });
    }

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
