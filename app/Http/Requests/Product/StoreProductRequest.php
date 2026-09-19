<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'tax_rate_override' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->withoutTrashed(),
            ],
            'image_url' => ['nullable', 'url', 'max:500'],
            'min_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // FIX: was unconditional merge() — merge() overwrites an explicit
        // false/0 the client sent. Only fill defaults for absent keys.
        // (DB default for min_stock_threshold is 5; validated here so the
        // API response echoes the effective value.)
        if (! $this->exists('is_active')) {
            $this->merge(['is_active' => true]);
        }

        if (! $this->exists('min_stock_threshold')) {
            $this->merge(['min_stock_threshold' => 5]);
        }
    }
}
