<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StartStockTakeRequest;
use App\Http\Requests\Inventory\SubmitStockTakeCountsRequest;
use App\Http\Resources\StockTakeResource;
use App\Models\StockTake;
use App\Services\StockTakeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Stock Take
 *
 * Physical count sessions: open → submit counts → approve (writes counts
 * into live stock). Counting is open to the branch; approval is managerial
 * (see StockTakePolicy).
 */
class StockTakeController extends Controller
{
    public function __construct(
        private readonly StockTakeService $stockTakeService
    ) {}

    /**
     * List stock takes
     *
     * Supports the service's branch/status filters via query string.
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', StockTake::class);

        $stockTakes = $this->stockTakeService->list($request->all());

        return StockTakeResource::collection($stockTakes);
    }

    /**
     * Start stock take
     *
     * Opens a session, optionally pre-seeding count lines for given products.
     */
    public function store(StartStockTakeRequest $request): JsonResponse
    {
        $stockTake = $this->stockTakeService->start($request->validated());

        return response()->json([
            'message' => 'Stock take started successfully.',
            'data' => new StockTakeResource($stockTake),
        ], 201);
    }

    /**
     * Show stock take
     *
     * Includes count lines with their products for the counting UI.
     */
    public function show(StockTake $stockTake): JsonResponse
    {
        $this->authorize('view', $stockTake);

        return response()->json([
            'data' => new StockTakeResource(
                $stockTake->load(['branch:id,name', 'creator:id,name', 'items.product:id,name,sku'])
            ),
        ]);
    }

    /**
     * Submit counted quantities
     *
     * Upserts per product, so recounts overwrite — re-submitting is allowed
     * for corrections. Touches no live stock (approval does that).
     */
    public function submitCounts(SubmitStockTakeCountsRequest $request, StockTake $stockTake): JsonResponse
    {
        $this->authorize('submitCounts', $stockTake);

        $stockTake = $this->stockTakeService->submitCounts($stockTake, $request->validated());

        return response()->json([
            'message' => 'Stock take counts submitted successfully.',
            'data' => new StockTakeResource($stockTake),
        ]);
    }

    /**
     * Approve stock take
     *
     * Writes counts into live stock levels with per-product adjustment
     * movements. Skips uncounted and zero-difference lines.
     */
    public function approve(StockTake $stockTake): JsonResponse
    {
        $this->authorize('approve', $stockTake);

        $stockTake = $this->stockTakeService->approve($stockTake);

        return response()->json([
            'message' => 'Stock take approved successfully.',
            'data' => new StockTakeResource($stockTake),
        ]);
    }

    /**
     * Variance report
     *
     * Read-only per-product system-vs-counted breakdown for review screens.
     */
    public function varianceReport(StockTake $stockTake): JsonResponse
    {
        $this->authorize('view', $stockTake);

        $report = $this->stockTakeService->varianceReport($stockTake);

        return response()->json([
            'data' => $report,
        ]);
    }
}
