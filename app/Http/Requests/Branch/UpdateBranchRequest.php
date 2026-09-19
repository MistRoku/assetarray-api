<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Branch update. Partial bodies allowed; code uniqueness ignores the branch
 * being edited (works with both bound models and raw route ids).
 */
class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('branch')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // FIX: route param may be a bound Branch model or a raw id string —
        // $branch?->id on a string throws Error. Normalise first.
        $routeParam = $this->route('branch');
        $branchId = $routeParam instanceof Branch ? $routeParam->getKey() : $routeParam;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::unique('branches', 'code')->ignore($branchId)->withoutTrashed(),
            ],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'operating_hours' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
