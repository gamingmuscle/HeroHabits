<?php

namespace App\Http\Requests\Auth;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ParentRegisterRequest extends FormRequest
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
        $rules = [
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'displayname' => ['required', 'string', 'max:50'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(6)
                    ->letters()
                    ->numbers(),
            ],
        ];

        // Add invitation code validation if invitation-only mode is enabled
        if (config('herohabits.registration.invitation_only', false)) {
            $rules['invitation_code'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $invitation = Invitation::where('code', strtoupper($value))->first();

                    if (!$invitation) {
                        $fail('Invalid invitation code.');
                        return;
                    }

                    if (!$invitation->isValid()) {
                        if ($invitation->used_at) {
                            $fail('This invitation code has already been used.');
                        } else {
                            $fail('This invitation code has expired.');
                        }
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Please enter a username.',
            'username.unique' => 'This username is already taken. Please choose another.',
            'username.max' => 'Username must not exceed 100 characters.',
            'displayname.required' => 'Please enter a display name.',
            'displayname.max' => 'Display name must not exceed 50 characters.',
            'password.required' => 'Please enter a password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'invitation_code.required' => 'Please enter an invitation code.',
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'displayname' => 'display name',
        ];
    }
}
