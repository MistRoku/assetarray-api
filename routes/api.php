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
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('profile', [AuthController::class, 'profile']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
        });

        Route::post('branches/{branch}/manager', [BranchController::class, 'assignManager']);
        Route::apiResource('branches', BranchController::class);

        Route::post('products/import', [ProductController::class, 'import']);
        Route::get('products/{product}/price-history', [ProductController::class, 'priceHistory']);
        Route::apiResource('products', ProductController::class);

        Route::prefix('inventory')->group(function () {
            Route::get('/', [InventoryController::class, 'index']);
            Route::post('adjust', [InventoryController::class, 'adjust']);
            Route::get('movements', [InventoryController::class, 'movements']);

            Route::prefix('transfers')->group(function () {
                Route::get('/', [StockTransferController::class, 'index']);
                Route::post('/', [StockTransferController::class, 'store']);
                Route::get('{transfer}', [StockTransferController::class, 'show']);
                Route::put('{transfer}/approve', [StockTransferController::class, 'approve']);
                Route::put('{transfer}/reject', [StockTransferController::class, 'reject']);
                Route::put('{transfer}/receive', [StockTransferController::class, 'receive']);
            });

            Route::prefix('stock-take')->group(function () {
                Route::get('/', [StockTakeController::class, 'index']);
                Route::post('/', [StockTakeController::class, 'store']);
                Route::get('{stockTake}', [StockTakeController::class, 'show']);
                Route::put('{stockTake}/items', [StockTakeController::class, 'submitCounts']);
                Route::put('{stockTake}/approve', [StockTakeController::class, 'approve']);
                Route::get('{stockTake}/variance-report', [StockTakeController::class, 'varianceReport']);
            });
        });

        Route::apiResource('suppliers', SupplierController::class);

        Route::prefix('purchase-orders')->group(function () {
            Route::get('/', [PurchaseOrderController::class, 'index']);
            Route::post('/', [PurchaseOrderController::class, 'store']);
            Route::get('{purchaseOrder}', [PurchaseOrderController::class, 'show']);
            Route::put('{purchaseOrder}/send', [PurchaseOrderController::class, 'send']);
            Route::post('{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
            Route::put('{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
        });

        Route::prefix('reports')->group(function () {
            Route::get('inventory-valuation', [ReportController::class, 'inventoryValuation']);
            Route::get('stock-movements', [ReportController::class, 'stockMovements']);
            Route::get('low-stock', [ReportController::class, 'lowStock']);
            Route::get('product-performance', [ReportController::class, 'productPerformance']);
            Route::get('transfers', [ReportController::class, 'transfers']);
            Route::get('export/{type}', [ReportController::class, 'export']);
        });

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('unread-count', [NotificationController::class, 'unreadCount']);
            Route::put('read-all', [NotificationController::class, 'markAllRead']);
            Route::put('{notification}/read', [NotificationController::class, 'markRead']);
        });

        Route::get('audit-logs', [AuditLogController::class, 'index']);
    });
});