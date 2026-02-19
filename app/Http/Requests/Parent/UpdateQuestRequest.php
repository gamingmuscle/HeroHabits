<?php

namespace App\Http\Requests\Parent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'gold_reward' => ['sometimes', 'required', 'integer', 'min:1', 'max:1000'],
            'max_turnins' => ['nullable', 'integer', 'min:1'],
            'turnin_period' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'trait_ids' => ['nullable', 'array'],
            'trait_ids.*' => ['integer', 'exists:traits,id'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Quest title is required.',
            'title.max' => 'Quest title must not exceed 100 characters.',
            'gold_reward.required' => 'Gold reward is required.',
            'gold_reward.min' => 'Gold reward must be at least 1.',
            'gold_reward.max' => 'Gold reward must not exceed 1000.',
        ];
    }
}
