<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The user full name field is required.',
            'name.string' => 'The user full name must be a valid text string.',
            'name.max' => 'The user full name may not be greater than 255 characters.',
            'name.min' => 'The user full name must be at least 1 character.',

            'email.required' => 'The email address field is required.',
            'email.email' => 'Please enter a valid email address format.',
            'email.unique' => 'This email address is already registered in the system.',
            'email.max' => 'The email address may not be greater than 255 characters.',

            'password.required' => 'The account password is required for new users.',
            'password.string' => 'The password must be a valid text string.',
            'password.min' => 'The password must be at least 8 characters long.',
            'password.max' => 'The password may not be greater than 255 characters.',
            'password.confirmed' => 'The password confirmation does not match.',

            'roles.array' => 'The assigned roles format is invalid.',

            'is_active.boolean' => 'The active status value must be true or false.',
            'is_locked.boolean' => 'The lock status value must be true or false.',
            'must_change_password.boolean' => 'The change password requirement must be true or false.',

            'remark.string' => 'The remark notes must be a valid text string.',
            'remark.max' => 'The remark notes may not exceed 1000 characters.',
            'reason.required' => 'A reason / change log is required when updating user data.',
            'reason.string' => 'The reason must be a valid text string.',
            'reason.max' => 'The reason may not exceed 1000 characters.',
        ];
    }
}
