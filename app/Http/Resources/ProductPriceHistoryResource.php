<?php

namespace App\Http\Resources;

use App\Models\ProductPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a price-change record. changed_by nests the acting user
 * (not the raw id) when loaded. Timed by changed_at, not created_at.
 *
 * @mixin ProductPriceHistory
 */
class ProductPriceHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'old_cost_price' => $this->old_cost_price,
            'new_cost_price' => $this->new_cost_price,
            'old_selling_price' => $this->old_selling_price,
            'new_selling_price' => $this->new_selling_price,
            'changed_by' => new UserResource($this->whenLoaded('changedBy')),
            'notes' => $this->notes,
            'changed_at' => $this->changed_at,
        ];
    }
}
