<?php

namespace App\Http\Resources;

use App\Models\StockTakeItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of one count line: system vs counted quantity plus the
 * precomputed variance (counted − system, set at submit time).
 *
 * @mixin StockTakeItem
 */
class StockTakeItemResource extends JsonResource
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
            'system_quantity' => $this->system_quantity,
            'counted_quantity' => $this->counted_quantity,
            'variance' => $this->variance,
            'note' => $this->note,
        ];
    }
}
