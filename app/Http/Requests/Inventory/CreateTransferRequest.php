<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTransferRequest extends FormRequest
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
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'from_branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'to_branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id'),
                'different:from_branch_id',
            ],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
