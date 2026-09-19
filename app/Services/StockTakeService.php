<?php

namespace App\Services;

use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockTake;
use App\Models\StockTakeItem;
use App\Support\StockTakeReferenceGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Physical count workflow: start (open) → submitCounts (submitted) →
 * approve (writes counts into stock + adjustment movements).
 *
 * system_quantity is snapshotted per line at submit time so the variance
 * always reflects "what the system said when you counted", even if stock
 * moved concurrently. Approval re-reads live stock under lock and writes
 * the difference as an adjustment movement per product.
 */
final class StockTakeService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * Open a count session, optionally pre-seeding lines for given products
     * with their current system quantities.
     *
     * @param  array{branch_id: int, notes?: string, product_ids?: array<int, int>}  $data
     */
    public function start(array $data): StockTake
    {
        return DB::transaction(function () use ($data) {
            $stockTake = StockTake::create([
                'reference' => $data['reference'] ?? StockTakeReferenceGenerator::make(),
                'branch_id' => $data['branch_id'],
                'created_by' => auth()->id(),
                'status' => StockTake::STATUS_OPEN,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['product_ids'])) {
                foreach ($data['product_ids'] as $productId) {
                    $systemQuantity = StockLevel::query()
                        ->where('product_id', $productId)
                        ->where('branch_id', $data['branch_id'])
                        ->value('quantity') ?? 0;

                    StockTakeItem::create([
                        'stock_take_id' => $stockTake->id,
                        'product_id' => $productId,
                        'system_quantity' => $systemQuantity,
                    ]);
                }
            }

            $this->auditLogService->log(
                action: 'created',
                entityType: StockTake::class,
                entityId: $stockTake->id,
                newValues: $stockTake->toArray()
            );

            return $stockTake->load(['branch:id,name', 'creator:id,name', 'items.product:id,name,sku']);
        });
    }

    /**
     * Record (or re-record) counted quantities. Upserts per product so recounts
     * overwrite earlier submissions; re-submitting from SUBMITTED back to
     * SUBMITTED is allowed for corrections. Stock levels are untouched here —
     * approve() is the only writer.
     *
     * @param  array{items: array<int, array{product_id: int, counted_quantity: int, note?: string}>}  $data
     *
     * @throws ValidationException When the take is approved/cancelled.
     */
    public function submitCounts(StockTake $stockTake, array $data): StockTake
    {
        return DB::transaction(function () use ($stockTake, $data) {
            if (! in_array($stockTake->status, [StockTake::STATUS_OPEN, StockTake::STATUS_SUBMITTED], true)) {
                throw ValidationException::withMessages([
                    'status' => ['This stock take cannot accept counts.'],
                ]);
            }

            foreach ($data['items'] as $item) {
                $systemQuantity = StockLevel::query()
                    ->where('product_id', $item['product_id'])
                    ->where('branch_id', $stockTake->branch_id)
                    ->value('quantity') ?? 0;

                $variance = (int) $item['counted_quantity'] - (int) $systemQuantity;

                StockTakeItem::updateOrCreate(
                    [
                        'stock_take_id' => $stockTake->id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'system_quantity' => $systemQuantity,
                        'counted_quantity' => $item['counted_quantity'],
                        'variance' => $variance,
                        'note' => $item['note'] ?? null,
                    ]
                );
            }

            $originalStatus = $stockTake->getOriginal('status');

            $stockTake->update(['status' => StockTake::STATUS_SUBMITTED]);

            $this->auditLogService->log(
                action: 'updated',
                entityType: StockTake::class,
                entityId: $stockTake->id,
                oldValues: ['status' => $originalStatus],
                newValues: ['status' => StockTake::STATUS_SUBMITTED]
            );

            // refresh()+load() instead of fresh(): fresh() is nullable.
            $stockTake->refresh();

            return $stockTake->load(['branch:id,name', 'creator:id,name', 'items.product:id,name,sku']);
        });
    }

    /**
     * Apply the submitted counts to live stock. Lines without a counted
     * quantity and zero-difference lines are skipped (no noise movements).
     * Terminal: stamps completed_at and flips to APPROVED.
     *
     * @throws ValidationException On non-submitted status.
     */
    public function approve(StockTake $stockTake): StockTake
    {
        return DB::transaction(function () use ($stockTake) {
            if ($stockTake->status !== StockTake::STATUS_SUBMITTED) {
                throw ValidationException::withMessages([
                    'status' => ['Only submitted stock takes can be approved.'],
                ]);
            }

            // Query items fresh instead of $stockTake->items: the relation may
            // not be eager-loaded on the passed model, which would silently
            // approve zero lines.
            foreach ($stockTake->items()->get() as $item) {
                if ($item->counted_quantity === null) {
                    continue;
                }

                $stock = StockLevel::query()
                    ->where('product_id', $item->product_id)
                    ->where('branch_id', $stockTake->branch_id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    $stock = StockLevel::create([
                        'product_id' => $item->product_id,
                        'branch_id' => $stockTake->branch_id,
                        'quantity' => 0,
                    ]);
                }

                $oldQuantity = $stock->quantity;
                $difference = (int) $item->counted_quantity - (int) $oldQuantity;

                if ($difference === 0) {
                    continue;
                }

                $stock->update(['quantity' => $item->counted_quantity]);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'branch_id' => $stockTake->branch_id,
                    'movement_type' => StockMovement::TYPE_ADJUSTMENT,
                    'quantity' => $difference,
                    'reference_type' => StockTakeItem::class,
                    'reference_id' => $item->id,
                    'reason' => 'Stock take adjustment: '.$stockTake->reference,
                    'created_by' => auth()->id(),
                ]);
            }

            $stockTake->update([
                'status' => StockTake::STATUS_APPROVED,
                'completed_at' => now(),
            ]);

            $this->auditLogService->log(
                action: 'updated',
                entityType: StockTake::class,
                entityId: $stockTake->id,
                oldValues: ['status' => StockTake::STATUS_SUBMITTED],
                newValues: ['status' => StockTake::STATUS_APPROVED]
            );

            // refresh()+load() instead of fresh(): fresh() is nullable.
            $stockTake->refresh();

            return $stockTake->load(['branch:id,name', 'creator:id,name', 'items.product:id,name,sku']);
        });
    }

    /**
     * Read-only per-product variance breakdown for review screens/exports.
     * Nullsafe product access: a deleted product still shows its quantities.
     */
    public function varianceReport(StockTake $stockTake)
    {
        return $stockTake->items()
            ->with('product:id,name,sku')
            ->get()
            ->map(fn (StockTakeItem $item) => [
                'product_id' => $item->product_id,
                'sku' => $item->product?->sku,
                'product_name' => $item->product?->name,
                'system_quantity' => $item->system_quantity,
                'counted_quantity' => $item->counted_quantity,
                'variance' => $item->variance,
                'note' => $item->note,
            ]);
    }

    /** Paginated stock takes with branch/status filters, newest first. */
    public function list(array $filters): LengthAwarePaginator
    {
        return StockTake::query()
            ->with(['branch:id,name', 'creator:id,name'])
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('branch_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $q, $status): Builder => $q->where('status', $status))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
