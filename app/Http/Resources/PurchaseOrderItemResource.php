<?php

namespace App\Http\Resources;

use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of one PO line. line_total is derived (unit_cost ×
 * quantity_ordered) — informational only; the PO total_amount on the parent
 * resource is the authoritative figure.
 *
 * @mixin PurchaseOrderItem
 */
class PurchaseOrderItemResource extends JsonResource
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
            'quantity_ordered' => $this->quantity_ordered,
            'quantity_received' => $this->quantity_received,
            'unit_cost' => $this->unit_cost,
            'line_total' => (float) $this->unit_cost * (int) $this->quantity_ordered,
        ];
    }
}
