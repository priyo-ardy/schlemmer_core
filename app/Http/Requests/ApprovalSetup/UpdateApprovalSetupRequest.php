<?php

namespace App\Http\Requests\ApprovalSetup;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApprovalSetupRequest extends FormRequest
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
        // Langsung ambil parameter 'id' dari URL
        $id = $this->route('id');

        return [
            'module' => [
                'required',
                'string',
                Rule::unique('approval_setups', 'module')
                    ->ignore($id)
                    ->whereNull('deleted_at'),
            ],
            'remark' => 'nullable|string',
            'reason' => 'required|string|min:5',
            'approver' => 'required|array|min:1',
            'approver.*.approver_id' => [
                'required',
                'integer',
                'distinct',
                'exists:users,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'module.required' => 'Module is required.',
            'module.unique'   => 'Approval setup for this module already exists.',

            'reason.required' => 'Reason for change is required.',
            'reason.min'      => 'Reason for change must be at least 5 characters.',

            'approver.required' => 'At least one approver must be assigned.',
            'approver.min'      => 'At least one approver must be assigned.',

            'approver.*.approver_id.required' => 'Approver selection is required.',
            'approver.*.approver_id.distinct' => 'Duplicate approver found. Each approver must be unique.',
            'approver.*.approver_id.exists'   => 'Selected approver is invalid or not found in system.',
        ];
    }
}
