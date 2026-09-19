<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Goods receipt batch. Line ids must exist; over-receiving beyond the
 * remaining ordered quantity is rejected per line in PurchaseOrderService.
 */
class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManagerOrAbove() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => [
                'required',
                'integer',
                Rule::exists('purchase_order_items', 'id'),
            ],
            'items.*.quantity_received' => ['required', 'integer', 'min:1'],
        ];
    }
}
