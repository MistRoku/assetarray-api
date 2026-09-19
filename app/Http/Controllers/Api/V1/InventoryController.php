<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockAdjustmentRequest;
use App\Http\Resources\StockLevelResource;
use App\Http\Resources\StockMovementResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Inventory
 *
 * Stock levels, adjustments, and movement history.
 *
 * Listing is open to all authenticated users (staff need visibility to do
 * counts); writes go through StockAdjustmentRequest (manager-only) and the
 * row-locked InventoryService. No price data appears here — valuation lives
 * in the gated ReportController.
 */
class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    /**
     * List stock levels
     *
     * @queryParam branch_id Filter by branch. Example: 1
     * @queryParam product_id Filter by product. Example: 1
     * @queryParam search Search by product name or SKU. Example: mouse
     * @queryParam per_page Results per page. Example: 15
     */
    public function index(Request $request): ResourceCollection
    {
        $stockLevels = $this->inventoryService->list($request->all());

        return StockLevelResource::collection($stockLevels);
    }

    /**
     * Adjust stock
     *
     * Applies a signed delta under row lock and writes a ledger row.
     * Negative results need a "correction" reason (enforced in the service).
     *
     * @bodyParam product_id integer required Product ID. Example: 1
     * @bodyParam branch_id integer required Branch ID. Example: 1
     * @bodyParam quantity integer required Positive or negative adjustment. Example: 10
     * @bodyParam reason string required Reason for adjustment. Example: Initial stock receipt
     */
    public function adjust(StockAdjustmentRequest $request): JsonResponse
    {
        $stock = $this->inventoryService->adjust($request->validated());

        return response()->json([
            'message' => 'Stock adjusted successfully.',
            'data' => new StockLevelResource($stock),
        ]);
    }

    /**
     * Stock movement history
     *
     * The append-only ledger, newest first. No amounts beyond per-row
     * unit/total figures — aggregates are reports, not this endpoint.
     *
     * @queryParam branch_id Filter by branch. Example: 1
     * @queryParam product_id Filter by product. Example: 1
     * @queryParam movement_type receipt, sale, adjustment, transfer_in, transfer_out. Example: receipt
     * @queryParam from Date from. Example: 2026-01-01
     * @queryParam to Date to. Example: 2026-12-31
     */
    public function movements(Request $request): ResourceCollection
    {
        $movements = $this->inventoryService->movements($request->all());

        return StockMovementResource::collection($movements);
    }
}
