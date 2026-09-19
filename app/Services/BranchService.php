<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Branch CRUD plus manager assignment.
 *
 * Deactivation is two-step (is_active=false + soft delete) so the branch
 * disappears from active scopes while its history stays queryable.
 */
final class BranchService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /** Paginated branches with search + active-state filters. */
    public function list(array $filters): LengthAwarePaginator
    {
        return Branch::query()
            ->withCount('users')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['is_active']), function (Builder $query) use ($filters): void {
                $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    /** Create a branch and audit it. Validation lives in StoreBranchRequest. */
    public function create(array $data): Branch
    {
        return DB::transaction(function () use ($data) {
            $branch = Branch::create($data);

            $this->auditLogService->log(
                action: 'created',
                entityType: Branch::class,
                entityId: $branch->id,
                newValues: $branch->toArray()
            );

            return $branch;
        });
    }

    /**
     * Update a branch. Only the changed keys are snapshotted for the audit
     * row, keeping old/new values small and relevant.
     */
    public function update(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            $oldValues = $branch->only(array_keys($data));

            $branch->update($data);

            $this->auditLogService->log(
                action: 'updated',
                entityType: Branch::class,
                entityId: $branch->id,
                oldValues: $oldValues,
                newValues: $branch->only(array_keys($data))
            );

            // refresh() (not fresh()): reloads in place and returns $this,
            // while fresh() returns ?Branch — a nullability lie the API
            // return type can't honor.
            $branch->refresh();

            return $branch;
        });
    }

    /**
     * Deactivate then soft-delete. The is_active flag hides the branch from
     * active scopes immediately; the soft delete preserves FK history.
     */
    public function deactivate(Branch $branch): void
    {
        DB::transaction(function () use ($branch) {
            $branch->update(['is_active' => false]);
            $branch->delete();

            $this->auditLogService->log(
                action: 'deleted',
                entityType: Branch::class,
                entityId: $branch->id,
                oldValues: $branch->only(['name', 'code', 'is_active'])
            );
        });
    }

    /**
     * Point a branch_manager user at this branch. Role is re-checked here
     * (defence in depth — the request already validates it) so a role
     * changed between validation and execution can't slip through.
     *
     * @throws ValidationException When the user is not a branch manager.
     */
    public function assignManager(Branch $branch, int $userId): User
    {
        return DB::transaction(function () use ($branch, $userId) {
            $manager = User::findOrFail($userId);

            if ($manager->role !== User::ROLE_BRANCH_MANAGER) {
                throw ValidationException::withMessages([
                    'user_id' => ['The selected user is not a branch manager.'],
                ]);
            }

            $oldBranchId = $manager->branch_id;

            $manager->update(['branch_id' => $branch->id]);

            $this->auditLogService->log(
                action: 'updated',
                entityType: User::class,
                entityId: $manager->id,
                oldValues: ['branch_id' => $oldBranchId],
                newValues: ['branch_id' => $branch->id]
            );

            $manager->refresh();

            return $manager->load('branch');
        });
    }
}
