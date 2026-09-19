<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AssignBranchManagerRequest;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Http\Resources\UserResource;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group Branches
 *
 * Manage physical or logical branches. All writes are super-admin only
 * (see BranchPolicy) — managers run branches, they don't reshape the org.
 */
class BranchController extends Controller
{
    public function __construct(
        private readonly BranchService $branchService
    ) {}

    /**
     * List branches
     *
     * @queryParam search Search by branch name or code. Example: Main
     * @queryParam is_active Filter by active status. Example: true
     * @queryParam per_page Results per page. Example: 15
     */
    public function index(Request $request): ResourceCollection
    {
        $this->authorize('viewAny', Branch::class);

        $branches = $this->branchService->list($request->all());

        return BranchResource::collection($branches);
    }

    /**
     * Create branch
     *
     * Super-admin only (policy + StoreBranchRequest).
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = $this->branchService->create($request->validated());

        return response()->json([
            'message' => 'Branch created successfully.',
            'data' => new BranchResource($branch),
        ], 201);
    }

    /**
     * Show branch
     *
     * Includes the user count (staff_count) via loadCount.
     */
    public function show(Branch $branch): JsonResponse
    {
        $this->authorize('view', $branch);

        return response()->json([
            'data' => new BranchResource($branch->loadCount('users')),
        ]);
    }

    /**
     * Update branch
     *
     * Super-admin only (policy + UpdateBranchRequest).
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $branch = $this->branchService->update($branch, $request->validated());

        return response()->json([
            'message' => 'Branch updated successfully.',
            'data' => new BranchResource($branch),
        ]);
    }

    /**
     * Deactivate branch
     *
     * Soft-deletes after flipping is_active — history keeps working.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $this->authorize('delete', $branch);

        $this->branchService->deactivate($branch);

        return response()->json([
            'message' => 'Branch deactivated successfully.',
        ]);
    }

    /**
     * Assign manager to branch
     *
     * Returns both sides of the assignment. The manager is wrapped in
     * UserResource (never a bare model) so no internal fields leak.
     *
     * @bodyParam user_id integer required The branch manager user ID. Example: 2
     */
    public function assignManager(AssignBranchManagerRequest $request, Branch $branch): JsonResponse
    {
        $manager = $this->branchService->assignManager($branch, (int) $request->validated('user_id'));

        $branch->refresh();

        return response()->json([
            'message' => 'Branch manager assigned successfully.',
            'data' => [
                'branch' => new BranchResource($branch),
                'manager' => new UserResource($manager),
            ],
        ]);
    }
}
