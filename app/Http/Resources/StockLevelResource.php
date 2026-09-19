<?php

namespace App\Http\Resources;

use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a stock level (one product's quantity at one branch).
 * See ProductResource for the mutual-nesting warning with product.
 *
 * @mixin StockLevel
 */
class StockLevelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'quantity' => $this->quantity,
            'updated_at' => $this->updated_at,
        ];
    }
}
