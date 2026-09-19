<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Suppliers
 *
 * Manage suppliers. Writes are managerial; deletion is super-admin only
 * (see SupplierPolicy).
 */
class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    /**
     * List suppliers
     *
     * Supports the service's name/email/contact search via query string.
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = $this->supplierService->list($request->all());

        return SupplierResource::collection($suppliers);
    }

    /**
     * Create supplier
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());

        return response()->json([
            'message' => 'Supplier created successfully.',
            'data' => new SupplierResource($supplier),
        ], 201);
    }

    /**
     * Show supplier
     */
    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return response()->json([
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Update supplier
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier = $this->supplierService->update($supplier, $request->validated());

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Deactivate supplier
     *
     * Super-admin only: flips is_active then soft-deletes so order history
     * keeps working.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $this->supplierService->deactivate($supplier);

        return response()->json([
            'message' => 'Supplier deactivated successfully.',
        ]);
    }
}
