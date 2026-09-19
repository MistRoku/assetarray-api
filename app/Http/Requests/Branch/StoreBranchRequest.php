<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Branch creation. Code uniqueness excludes trashed rows (Branch is
 * soft-deletable). is_active defaults to true only when omitted.
 */
class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Branch::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // FIX: was string rule 'unique:branches,code' — Branch uses
            // SoftDeletes, so a trashed branch's code would pass validation
            // then fail on the DB UNIQUE constraint. withoutTrashed() makes
            // the intent explicit and consistent with Update.
            'code' => ['required', 'string', 'max:10', Rule::unique('branches', 'code')->withoutTrashed()],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'operating_hours' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Only default when the key is absent — boolean(false) is a real
        // value and must be preserved.
        if (! $this->exists('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
