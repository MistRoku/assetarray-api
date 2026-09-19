<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Product list wrapper. $collects maps every item to ProductResource.
 * NOTE: paginators add links/meta around this automatically — this toArray
 * only shapes the inner `data` key, it does not remove pagination.
 */
class ProductCollection extends ResourceCollection
{
    /**
     * The resource each collection item is mapped to.
     *
     * @var string
     */
    public $collects = ProductResource::class;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
