<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CreateTransferRequest;
use App\Http\Requests\Inventory\RejectTransferRequest;
use App\Http\Resources\StockTransferResource;
use App\Models\StockTransfer;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Stock Transfers
 *
 * Branch-to-branch transfer workflow: request → approve/reject → receive.
 * Approval rights sit with the destination branch's manager (see
 * StockTransferPolicy) — the receiver accepts the stock.
 */
class StockTransferController extends Controller
{
    public function __construct(
        private readonly TransferService $transferService
    ) {}

    /**
     * List transfers
     *
     * Supports the service's status/branch/date filters via query string.
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', StockTransfer::class);

        $transfers = $this->transferService->list($request->all());

        return StockTransferResource::collection($transfers);
    }

    /**
     * Create transfer request
     *
     * Reserves nothing yet — source stock is re-checked and decremented at
     * approval time, so request against live availability.
     */
    public function store(CreateTransferRequest $request): JsonResponse
    {
        $transfer = $this->transferService->create($request->validated());

        return response()->json([
            'message' => 'Transfer request created successfully.',
            'data' => new StockTransferResource($transfer),
        ], 201);
    }

    /**
     * Show transfer
     *
     * Includes requester/approver names for the approval trail.
     */
    public function show(StockTransfer $transfer): JsonResponse
    {
        $this->authorize('view', $transfer);

        return response()->json([
            'data' => new StockTransferResource(
                $transfer->load(['product:id,name,sku', 'fromBranch:id,name', 'toBranch:id,name', 'requestedBy:id,name', 'approvedBy:id,name'])
            ),
        ]);
    }

    /**
     * Approve transfer
     *
     * Decrements the source branch. Rejects when source stock dropped below
     * the requested quantity since the request was created.
     */
    public function approve(StockTransfer $transfer): JsonResponse
    {
        $this->authorize('approve', $transfer);

        $transfer = $this->transferService->approve($transfer);

        return response()->json([
            'message' => 'Transfer approved successfully.',
            'data' => new StockTransferResource($transfer),
        ]);
    }

    /**
     * Reject transfer
     *
     * Terminal state, no stock moves. The reason is stored for the audit trail.
     *
     * @bodyParam reason string required Rejection reason. Example: Incorrect destination branch
     */
    public function reject(RejectTransferRequest $request, StockTransfer $transfer): JsonResponse
    {
        $this->authorize('reject', $transfer);

        $transfer = $this->transferService->reject($transfer, $request->validated('reason'));

        return response()->json([
            'message' => 'Transfer rejected successfully.',
            'data' => new StockTransferResource($transfer),
        ]);
    }

    /**
     * Receive transfer
     *
     * Increments the destination branch and closes the transfer. Creates the
     * destination stock level on first receipt.
     */
    public function receive(StockTransfer $transfer): JsonResponse
    {
        $this->authorize('receive', $transfer);

        $transfer = $this->transferService->receive($transfer);

        return response()->json([
            'message' => 'Transfer received successfully.',
            'data' => new StockTransferResource($transfer),
        ]);
    }
}
