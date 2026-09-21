<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a product.
 *
 * Nesting guard: category/supplier/stockLevels render only when eager-loaded
 * (whenLoaded), so list endpoints stay light. WARNING — do not eager-load
 * BOTH product.stockLevels AND stockLevel.product in one response: the two
 * resources nest each other and will recurse until memory exhaustion.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result = [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'supplier_id' => $this->supplier_id,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'selling_price' => $this->selling_price,
            'tax_rate_override' => $this->tax_rate_override,
            'barcode' => $this->barcode,
            'image_url' => $this->image_url,
            'min_stock_threshold' => $this->min_stock_threshold,
            'is_active' => $this->is_active,
            'stock_levels' => StockLevelResource::collection($this->whenLoaded('stockLevels')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];

        // Only include cost_price for managers and above
        if ($request->user()?->isManagerOrAbove()) {
            $result['cost_price'] = $this->cost_price;
        }

        return $result;
    }
}
