<?php

namespace App\Http\Requests\Branch;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Manager assignment. The exists rule pre-filters to branch_manager users;
 * BranchService re-checks the role at write time (defence in depth).
 */
class AssignBranchManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assignManager', $this->route('branch')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                // FIX: was hardcoded 'branch_manager' string — use the model
                // constant so a role rename can't silently desync.
                Rule::exists('users', 'id')->where('role', User::ROLE_BRANCH_MANAGER),
            ],
        ];
    }
}
