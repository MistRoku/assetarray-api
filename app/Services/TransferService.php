<?php

namespace App\Services;

use App\Events\TransferRequested;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Support\TransferCodeGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransferService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            if ((int) $data['from_branch_id'] === (int) $data['to_branch_id']) {
                throw ValidationException::withMessages([
                    'to_branch_id' => ['Source and destination branches must be different.'],
                ]);
            }

            if ((int) $data['quantity'] <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['Quantity must be greater than zero.'],
                ]);
            }

            $stock = StockLevel::query()
                ->where('product_id', $data['product_id'])
                ->where('branch_id', $data['from_branch_id'])
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->quantity < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => ['Insufficient stock at source branch.'],
                ]);
            }

            $transfer = StockTransfer::create([
                'transfer_code' => $data['transfer_code'] ?? TransferCodeGenerator::make(),
                'from_branch_id' => $data['from_branch_id'],
                'to_branch_id' => $data['to_branch_id'],
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'status' => StockTransfer::STATUS_PENDING,
                'requested_by' => auth()->id(),
            ]);

            event(new TransferRequested($transfer));

            $this->auditLogService->log(
                action: 'created',
                entityType: StockTransfer::class,
                entityId: $transfer->id,
                newValues: $transfer->toArray()
            );

            return $transfer->load(['product:id,name,sku', 'fromBranch:id,name', 'toBranch:id,name']);
        });
    }

    public function approve(StockTransfer $transfer): StockTransfer
    {
        return DB::transaction(function () use ($transfer) {
            if ($transfer->status !== StockTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending transfers can be approved.'],
                ]);
            }

            $sourceStock = StockLevel::query()
                ->where('product_id', $transfer->product_id)
                ->where('branch_id', $transfer->from_branch_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sourceStock->quantity < $transfer->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Source stock changed. Transfer cannot be approved.'],
                ]);
            }

            $sourceStock->update(['quantity' => $sourceStock->quantity - $transfer->quantity]);

            StockMovement::create([
                'product_id' => $transfer->product_id,
                'branch_id' => $transfer->from_branch_id,
                'movement_type' => StockMovement::TYPE_TRANSFER_OUT,
                'quantity' => -$transfer->quantity,
                'reference_type' => StockTransfer::class,
                'reference_id' => $transfer->id,
                'reason' => 'Transfer approved: '.$transfer->transfer_code,
                'created_by' => auth()->id(),
            ]);

            $transfer->update([
                'status' => StockTransfer::STATUS_APPROVED,
                'approved_by' => auth()->id(),
            ]);

            $this->auditLogService->log(
                action: 'updated',
                entityType: StockTransfer::class,
                entityId: $transfer->id,
                oldValues: ['status' => StockTransfer::STATUS_PENDING],
                newValues: ['status' => StockTransfer::STATUS_APPROVED]
            );

            return $transfer->fresh(['product:id,name,sku', 'fromBranch:id,name', 'toBranch:id,name']);
        });
    }

    public function reject(StockTransfer $transfer, string $reason): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $reason) {
            if ($transfer->status !== StockTransfer::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending transfers can be rejected.'],
                ]);
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_REJECTED,
                'rejected_reason' => $reason,
            ]);

            $this->auditLogService->log(
                action: 'updated',
                entityType: StockTransfer::class,
                entityId: $transfer->id,
                oldValues: ['status' => StockTransfer::STATUS_PENDING],
                newValues: ['status' => StockTransfer::STATUS_REJECTED, 'rejected_reason' => $reason]
            );

            return $transfer->fresh(['product:id,name,sku', 'fromBranch:id,name', 'toBranch:id,name']);
        });
    }

    public function receive(StockTransfer $transfer): StockTransfer
    {
        return DB::transaction(function () use ($transfer) {
            if ($transfer->status !== StockTransfer::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'status' => ['Only approved transfers can be received.'],
                ]);
            }

            $destinationStock = StockLevel::query()
                ->where('product_id', $transfer->product_id)
                ->where('branch_id', $transfer->to_branch_id)
                ->lockForUpdate()
                ->first();

            if (! $destinationStock) {
                $destinationStock = StockLevel::create([
                    'product_id' => $transfer->product_id,
                    'branch_id' => $transfer->to_branch_id,
                    'quantity' => 0,
                ]);
            }

            $destinationStock->update(['quantity' => $destinationStock->quantity + $transfer->quantity]);

            StockMovement::create([
                'product_id' => $transfer->product_id,
                'branch_id' => $transfer->to_branch_id,
                'movement_type' => StockMovement::TYPE_TRANSFER_IN,
                'quantity' => $transfer->quantity,
                'reference_type' => StockTransfer::class,
                'reference_id' => $transfer->id,
                'reason' => 'Transfer received: '.$transfer->transfer_code,
                'created_by' => auth()->id(),
            ]);

            $transfer->update([
                'status' => StockTransfer::STATUS_RECEIVED,
                'transferred_at' => now(),
            ]);

            $this->auditLogService->log(
                action: 'updated',
                entityType: StockTransfer::class,
                entityId: $transfer->id,
                oldValues: ['status' => StockTransfer::STATUS_APPROVED],
                newValues: ['status' => StockTransfer::STATUS_RECEIVED]
            );

            return $transfer->fresh(['product:id,name,sku', 'fromBranch:id,name', 'toBranch:id,name']);
        });
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return StockTransfer::query()
            ->with(['product:id,name,sku', 'fromBranch:id,name', 'toBranch:id,name', 'requestedBy:id,name', 'approvedBy:id,name'])
            ->when($filters['status'] ?? null, fn (Builder $q, $status): Builder => $q->where('status', $status))
            ->when($filters['from_branch_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('from_branch_id', $id))
            ->when($filters['to_branch_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('to_branch_id', $id))
            ->when($filters['from'] ?? null, fn (Builder $q, $from): Builder => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to): Builder => $q->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
