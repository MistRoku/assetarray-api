<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a branch. staff_count only appears when the query used
 * withCount('users') (see BranchService::list) — otherwise the key is
 * omitted entirely, not null.
 *
 * @mixin Branch
 */
class BranchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'phone' => $this->phone,
            'tax_rate' => $this->tax_rate,
            'operating_hours' => $this->operating_hours,
            'is_active' => $this->is_active,
            'staff_count' => $this->whenCounted('users'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Present only for soft-deleted rows (Branch uses SoftDeletes).
            'deleted_at' => $this->deleted_at,
        ];
    }
}
