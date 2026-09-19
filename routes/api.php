<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\StockTakeController;
use App\Http\Controllers\Api\V1\StockTransferController;
use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Legacy Sanctum sample route — kept for reference.
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Versioned API. Everything below lives under /api/v1/... Auth-sensitive
// routes sit behind auth:sanctum + the active-account gate; per-action
// authorization additionally happens in FormRequests, policies and gates.
Route::prefix('v1')->group(function (): void {
    // Public: login + password reset (rate-limit friendly, no auth yet).
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        // Auth self-service.
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::get('auth/me', [AuthController::class, 'profile']);
        Route::put('auth/me', [AuthController::class, 'updateProfile']);

        // Branches (writes are super-admin only — see BranchPolicy).
        Route::apiResource('branches', BranchController::class)->except(['update']);
        Route::put('branches/{branch}', [BranchController::class, 'update']);
        Route::post('branches/{branch}/manager', [BranchController::class, 'assignManager']);

        // Products catalogue + CSV import + price history.
        Route::post('products/import', [ProductController::class, 'import']);
        Route::get('products/{product}/price-history', [ProductController::class, 'priceHistory']);
        Route::apiResource('products', ProductController::class);

        // Inventory levels, adjustments and the movement ledger.
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::post('inventory/adjust', [InventoryController::class, 'adjust']);
        Route::get('inventory/movements', [InventoryController::class, 'movements']);

        // Transfers: request → approve/reject → receive (no update/delete).
        Route::apiResource('transfers', StockTransferController::class)->only(['index', 'show', 'store']);
        Route::post('transfers/{transfer}/approve', [StockTransferController::class, 'approve']);
        Route::post('transfers/{transfer}/reject', [StockTransferController::class, 'reject']);
        Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive']);

        // Stock takes: open → submit counts → approve, plus variance view.
        Route::apiResource('stock-takes', StockTakeController::class)->only(['index', 'show', 'store']);
        Route::post('stock-takes/{stockTake}/counts', [StockTakeController::class, 'submitCounts']);
        Route::post('stock-takes/{stockTake}/approve', [StockTakeController::class, 'approve']);
        Route::get('stock-takes/{stockTake}/variance', [StockTakeController::class, 'varianceReport']);

        // Suppliers.
        Route::apiResource('suppliers', SupplierController::class);

        // Purchase orders: draft → send → receive[] → received, or cancel.
        Route::apiResource('purchase-orders', PurchaseOrderController::class)->only(['index', 'show', 'store']);
        Route::post('purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'send']);
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);

        // Read-only reports + CSV exports (manager-or-above gate).
        Route::get('reports/inventory-valuation', [ReportController::class, 'inventoryValuation']);
        Route::get('reports/stock-movements', [ReportController::class, 'stockMovements']);
        Route::get('reports/low-stock', [ReportController::class, 'lowStock']);
        Route::get('reports/product-performance', [ReportController::class, 'productPerformance']);
        Route::get('reports/transfers', [ReportController::class, 'transfers']);
        Route::get('reports/export/{type}', [ReportController::class, 'export']);

        // Own inbox only — no cross-user listing exists by design.
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);

        // Immutable audit trail (super-admin gate).
        Route::get('audit-logs', [AuditLogController::class, 'index']);
    });
});
