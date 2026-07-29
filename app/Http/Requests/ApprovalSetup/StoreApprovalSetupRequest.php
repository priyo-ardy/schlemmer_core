<?php

namespace App\Http\Requests\ApprovalSetup;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalSetupRequest extends FormRequest
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
            'module' => 'required|string|unique:approval_setups,module',
            'remark' => 'nullable|string',
            'approver' => 'required|array|min:1',
            'approver.*.approver_id' => 'required|integer|exists:users,id'
        ];
    }

    public function messages()
    {
        return [
            'module.required' => 'Approval module is required.',
            'module.unique' => 'Approval setup for this module already exists.',
            'approver.required' => 'At least one approver is required.',
            'approver.*.approver_id.required' => 'Approver selection is required.',
            'approver.*.approver_id.exists' => 'Selected approver is invalid.',
        ];
    }
}
