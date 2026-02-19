<?php

namespace App\Http\Requests\Child;

use Illuminate\Foundation\Http\FormRequest;

class CompleteQuestRequest extends FormRequest
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
            // No additional fields required - quest ID comes from route parameter
            // Child ID comes from authenticated user
            // Date defaults to today
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [];
    }
}
