<?php

namespace App\Http\Requests\Parent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChildRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:50'],
            'age' => ['nullable', 'integer', 'min:1', 'max:18'],
            'avatar_image' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'pin' => ['sometimes', 'required', 'string', 'digits:4'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Child name is required.',
            'name.max' => 'Child name must not exceed 50 characters.',
            'age.min' => 'Age must be at least 1.',
            'age.max' => 'Age must not exceed 18.',
            'pin.required' => 'PIN is required.',
            'pin.digits' => 'PIN must be exactly 4 digits.',
        ];
    }
}
