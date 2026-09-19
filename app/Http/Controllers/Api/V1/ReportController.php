<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Reports
 *
 * Read-only reporting endpoints and CSV exports. Everything money- or
 * margin-adjacent is gated manager-or-above (abilities defined in
 * AppServiceProvider); plain movement/low-stock reads stay open to
 * authenticated users.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Inventory valuation report
     *
     * On-hand value per product per branch, valued at cost (not revenue).
     */
    public function inventoryValuation(Request $request): JsonResponse
    {
        $this->authorize('manager-or-above');

        return response()->json([
            'data' => $this->reportService->inventoryValuation($request->all()),
        ]);
    }

    /**
     * Stock movement report
     *
     * Filtered ledger view (branch/product/type/date via query string).
     */
    public function stockMovements(Request $request): JsonResponse
    {
        $this->authorize('manager-or-above');

        return response()->json([
            'data' => $this->reportService->stockMovements($request->all()),
        ]);
    }

    /**
     * Low stock report
     *
     * Every stock row currently below its product's threshold.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $this->authorize('manager-or-above');

        return response()->json([
            'data' => $this->reportService->lowStock($request->all()),
        ]);
    }

    /**
     * Product performance report
     *
     * Units sold + revenue per product from sale movements. Revenue prefers
     * recorded totals, falling back to current selling price (see service).
     */
    public function productPerformance(Request $request): JsonResponse
    {
        $this->authorize('manager-or-above');

        return response()->json([
            'data' => $this->reportService->productPerformance($request->all()),
        ]);
    }

    /**
     * Transfer history report
     */
    public function transfers(Request $request): JsonResponse
    {
        $this->authorize('manager-or-above');

        return response()->json([
            'data' => $this->reportService->transferHistory($request->all()),
        ]);
    }

    /**
     * CSV export
     *
     * Streams the report so memory stays flat regardless of row count.
     * Unknown types 404 via abort_unless (the service would return empty).
     *
     * Supported types:
     * inventory-valuation, low-stock, stock-movements, product-performance, transfers
     */
    public function export(Request $request, string $type): StreamedResponse
    {
        $this->authorize('manager-or-above');

        $allowed = [
            'inventory-valuation',
            'low-stock',
            'stock-movements',
            'product-performance',
            'transfers',
        ];

        abort_unless(in_array($type, $allowed, true), 404, 'Unsupported report type.');

        $filename = $type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($type, $request) {
            $handle = fopen('php://output', 'w');

            $rows = $this->reportService->exportData($type, $request->all());

            if (! empty($rows)) {
                fputcsv($handle, array_keys(reset($rows)));
            }

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
