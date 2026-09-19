<?php

namespace App\Http\Resources;

use App\Models\StockTake;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a stock take session with its count lines.
 * created_by nests the creator user (not the raw id) when loaded.
 *
 * @mixin StockTake
 */
class StockTakeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'status' => $this->status,
            'notes' => $this->notes,
            'completed_at' => $this->completed_at,
            'items' => StockTakeItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
