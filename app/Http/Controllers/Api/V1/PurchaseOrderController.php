<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\ReceivePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Purchase Orders
 *
 * Supplier purchase order lifecycle: draft → sent → partially_received →
 * received (or cancelled). Goods may arrive in multiple batches — each
 * receive call books one batch and recomputes the header status.
 */
class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService
    ) {}

    /**
     * List purchase orders
     *
     * Supports the service's status/supplier/branch filters via query string.
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $pos = $this->purchaseOrderService->list($request->all());

        return PurchaseOrderResource::collection($pos);
    }

    /**
     * Create purchase order
     *
     * Always starts as draft; line-item totals are computed server-side.
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $po = $this->purchaseOrderService->create($request->validated());

        return response()->json([
            'message' => 'Purchase order created successfully.',
            'data' => new PurchaseOrderResource($po),
        ], 201);
    }

    /**
     * Show purchase order
     *
     * Includes lines with their per-line received progress.
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('view', $purchaseOrder);

        return response()->json([
            'data' => new PurchaseOrderResource(
                $purchaseOrder->load(['supplier:id,name', 'branch:id,name', 'orderedBy:id,name', 'items.product:id,name,sku'])
            ),
        ]);
    }

    /**
     * Send purchase order
     *
     * Draft → sent. One-way for this step; from here only receive or cancel.
     */
    public function send(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('send', $purchaseOrder);

        $po = $this->purchaseOrderService->send($purchaseOrder);

        return response()->json([
            'message' => 'Purchase order marked as sent.',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Receive goods
     *
     * Books one batch against the lines (over-receiving rejected per line),
     * bumps branch stock, and recomputes the header status. Fires
     * PurchaseOrderReceived per batch — partial or complete.
     */
    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('receive', $purchaseOrder);

        $po = $this->purchaseOrderService->receiveGoods($purchaseOrder, $request->validated());

        return response()->json([
            'message' => 'Goods received successfully.',
            'data' => new PurchaseOrderResource($po),
        ]);
    }

    /**
     * Cancel purchase order
     *
     * Stops future receipts; already-received stock stays on the shelves.
     * Fully received or already-cancelled orders can't be cancelled.
     */
    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('cancel', $purchaseOrder);

        $po = $this->purchaseOrderService->cancel($purchaseOrder);

        return response()->json([
            'message' => 'Purchase order cancelled successfully.',
            'data' => new PurchaseOrderResource($po),
        ]);
    }
}
