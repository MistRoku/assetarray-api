<?php

namespace App\Http\Requests\Supplier;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Supplier::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            // FIX: was string rule 'unique:suppliers,email' — Supplier uses
            // SoftDeletes, so withoutTrashed() keeps validation consistent
            // with the DB constraint.
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->withoutTrashed()],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->exists('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
