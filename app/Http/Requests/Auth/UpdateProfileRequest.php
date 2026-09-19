<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Self-service profile update. 'sometimes' rules allow partial PATCH-style
 * bodies; email uniqueness ignores the caller's own row.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                // FIX: was $this->user()->id — crashes when guest hits the
                // endpoint (authorize() runs before rules(), but rules() is
                // still evaluated to build the validator error bag).
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
