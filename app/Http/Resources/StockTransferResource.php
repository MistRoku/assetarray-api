<?php

namespace App\Http\Resources;

use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a stock transfer. requested_by/approved_by carry nested
 * users (not raw ids) when those relations are loaded — see
 * StockMovementResource for the same convention.
 *
 * @mixin StockTransfer
 */
class StockTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_code' => $this->transfer_code,
            'from_branch_id' => $this->from_branch_id,
            'from_branch' => new BranchResource($this->whenLoaded('fromBranch')),
            'to_branch_id' => $this->to_branch_id,
            'to_branch' => new BranchResource($this->whenLoaded('toBranch')),
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => $this->quantity,
            'status' => $this->status,
            'requested_by' => new UserResource($this->whenLoaded('requestedBy')),
            'approved_by' => new UserResource($this->whenLoaded('approvedBy')),
            'rejected_reason' => $this->rejected_reason,
            'transferred_at' => $this->transferred_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
