<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ImportProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductPriceHistoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Products
 *
 * Manage the product and asset catalogue.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * List products
     *
     * Branch scoping changes the stock filters: with branch_id they (and the
     * eager-loaded stock levels) apply to that branch only.
     *
     * @queryParam search Search by name or SKU. Example: keyboard
     * @queryParam category_id Filter by category ID. Example: 1
     * @queryParam branch_id Filter by branch stock. Example: 1
     * @queryParam stock_status in_stock, low_stock, out_of_stock. Example: low_stock
     * @queryParam per_page Results per page. Example: 15
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->productService->list($request->all());

        return new ProductCollection($products);
    }

    /**
     * Create product
     *
     * The SKU is auto-generated when omitted (see Product::booted()).
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Show product
     *
     * Includes category, supplier and per-branch stock (with branch names).
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return response()->json([
            'data' => new ProductResource($product->load(['category', 'supplier', 'stockLevels.branch'])),
        ]);
    }

    /**
     * Update product
     *
     * Price changes are journaled automatically by ProductObserver.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update($product, $request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Soft delete product
     *
     * The SKU stays reserved (withTrashed uniqueness) so it can't be recycled.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    /**
     * Bulk CSV import products
     *
     * Queued — returns 202 immediately, rows are processed by
     * ProcessProductCsvImportJob. Per-row failures are logged, not fatal.
     *
     * Expected CSV headers:
     * name, category, description, cost_price, selling_price, min_stock_threshold, barcode
     */
    public function import(ImportProductRequest $request): JsonResponse
    {
        $this->productService->importCsv($request->file('file'));

        return response()->json([
            'message' => 'Product import queued successfully.',
        ], 202);
    }

    /**
     * Product price history
     *
     * Manager-only: exposes cost prices (see ProductPolicy).
     */
    public function priceHistory(Product $product): JsonResponse
    {
        $this->authorize('priceHistory', $product);

        $history = $this->productService->priceHistory($product);

        return response()->json([
            'data' => ProductPriceHistoryResource::collection($history),
        ]);
    }
}
