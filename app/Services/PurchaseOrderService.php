<?php

namespace App\Services;

use App\Events\PurchaseOrderReceived;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Support\PurchaseOrderNumberGenerator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PurchaseOrderService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'po_number' => $data['po_number'] ?? PurchaseOrderNumberGenerator::make(),
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'status' => PurchaseOrder::STATUS_DRAFT,
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
                'ordered_by' => auth()->id(),
                'ordered_at' => now(),
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $lineTotal = (float) $item['quantity_ordered'] * (float) $item['unit_cost'];
                $total += $lineTotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_cost'],
                ]);
            }

            $po->update(['total_amount' => $total]);

            $this->auditLogService->log(
                action: 'created',
                entityType: PurchaseOrder::class,
                entityId: $po->id,
                newValues: $po->toArray()
            );

            return $po->load(['supplier:id,name', 'branch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function send(PurchaseOrder $po): PurchaseOrder
    {
        return DB::transaction(function () use ($po) {
            if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'status' => ['Only draft purchase orders can be sent.'],
                ]);
            }

            $po->update(['status' => PurchaseOrder::STATUS_SENT]);

            $this->auditLogService->log(
                action: 'updated',
                entityType: PurchaseOrder::class,
                entityId: $po->id,
                oldValues: ['status' => PurchaseOrder::STATUS_DRAFT],
                newValues: ['status' => PurchaseOrder::STATUS_SENT]
            );

            return $po->fresh(['supplier:id,name', 'branch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function receiveGoods(PurchaseOrder $po, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data) {
            if (! in_array($po->status, [PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Only sent or partially received POs can receive goods.'],
                ]);
            }

            $originalStatus = $po->getOriginal('status');

            foreach ($data['items'] as $receivedItem) {
                $item = $po->items()->findOrFail($receivedItem['purchase_order_item_id']);

                $remaining = $item->quantity_ordered - $item->quantity_received;

                if ((int) $receivedItem['quantity_received'] <= 0) {
                    throw ValidationException::withMessages([
                        'items' => ['Received quantity must be greater than zero.'],
                    ]);
                }

                if ($receivedItem['quantity_received'] > $remaining) {
                    throw ValidationException::withMessages([
                        'items' => ['Received quantity exceeds remaining ordered quantity for product ID '.$item->product_id.'.'],
                    ]);
                }

                $item->increment('quantity_received', $receivedItem['quantity_received']);

                $stock = StockLevel::query()
                    ->where('product_id', $item->product_id)
                    ->where('branch_id', $po->branch_id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    $stock = StockLevel::create([
                        'product_id' => $item->product_id,
                        'branch_id' => $po->branch_id,
                        'quantity' => 0,
                    ]);
                }

                $stock->increment('quantity', $receivedItem['quantity_received']);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'branch_id' => $po->branch_id,
                    'movement_type' => StockMovement::TYPE_RECEIPT,
                    'quantity' => $receivedItem['quantity_received'],
                    'unit_price' => $item->unit_cost,
                    'total_amount' => (float) $item->unit_cost * (int) $receivedItem['quantity_received'],
                    'reference_type' => PurchaseOrderItem::class,
                    'reference_id' => $item->id,
                    'reason' => 'Goods received for PO '.$po->po_number,
                    'created_by' => auth()->id(),
                ]);
            }

            $allReceived = $po->items()
                ->whereColumn('quantity_received', '<', 'quantity_ordered')
                ->doesntExist();

            $someReceived = $po->items()
                ->where('quantity_received', '>', 0)
                ->exists();

            $status = $po->status;

            if ($allReceived) {
                $status = PurchaseOrder::STATUS_RECEIVED;
            } elseif ($someReceived) {
                $status = PurchaseOrder::STATUS_PARTIALLY_RECEIVED;
            }

            $po->update([
                'status' => $status,
                'received_at' => $allReceived ? now() : null,
            ]);

            event(new PurchaseOrderReceived($po));

            $this->auditLogService->log(
                action: 'updated',
                entityType: PurchaseOrder::class,
                entityId: $po->id,
                oldValues: ['status' => $originalStatus],
                newValues: ['status' => $status]
            );

            return $po->fresh(['supplier:id,name', 'branch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        return DB::transaction(function () use ($po) {
            if (in_array($po->status, [PurchaseOrder::STATUS_RECEIVED, PurchaseOrder::STATUS_CANCELLED], true)) {
                throw ValidationException::withMessages([
                    'status' => ['This purchase order cannot be cancelled.'],
                ]);
            }

            $originalStatus = $po->getOriginal('status');

            $po->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

            $this->auditLogService->log(
                action: 'updated',
                entityType: PurchaseOrder::class,
                entityId: $po->id,
                oldValues: ['status' => $originalStatus],
                newValues: ['status' => PurchaseOrder::STATUS_CANCELLED]
            );

            return $po->fresh(['supplier:id,name', 'branch:id,name', 'items.product:id,name,sku']);
        });
    }

    public function list(array $filters): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with(['supplier:id,name', 'branch:id,name', 'orderedBy:id,name'])
            ->when($filters['status'] ?? null, fn (Builder $q, $status): Builder => $q->where('status', $status))
            ->when($filters['supplier_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('supplier_id', $id))
            ->when($filters['branch_id'] ?? null, fn (Builder $q, $id): Builder => $q->where('branch_id', $id))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
