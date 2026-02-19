<?php

namespace App\Http\Requests\Parent;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreasureRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'gold_cost' => ['required', 'integer', 'min:1', 'max:10000'],
            'is_available' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Treasure name is required.',
            'title.max' => 'Treasure name must not exceed 100 characters.',
            'gold_cost.required' => 'Gold cost is required.',
            'gold_cost.min' => 'Gold cost must be at least 1.',
            'gold_cost.max' => 'Gold cost must not exceed 10000.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults if not provided
        if (!$this->has('is_available')) {
            $this->merge(['is_available' => true]);
        }

        // Strip HTML tags from description for XSS protection
        if ($this->has('description')) {
            $this->merge([
                'description' => strip_tags($this->description)
            ]);
        }
    }
}
