<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150|unique:users,email',
            'password' => 'required|string|min:8'
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Maximum 150 characters',
            'email.max' => 'Maximum 150 characters',
            'email.unique' => 'This email address already registered',
            'password.min' => 'The password must be at least 8 characters long'
        ];
    }
}
