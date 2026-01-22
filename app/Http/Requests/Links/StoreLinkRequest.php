<?php

namespace App\Http\Requests\Links;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLinkRequest extends FormRequest
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
            'destination_url' => [
                'required',
                'string',
                'max:2048',
                'url',
                'starts_with:http://,https://',
            ],
            'domain_id' => [
                'required',
                'integer',
                Rule::exists('domains', 'id'),
            ],
            'alias' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/',
            ],
            'password' => [
                'nullable',
                'string',
                'min:6',
                'max:255',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_url.required' => 'Enter a destination URL.',
            'destination_url.url' => 'Enter a valid URL.',
            'destination_url.starts_with' => 'URLs must start with http:// or https://.',
            'domain_id.required' => 'Choose a domain.',
            'domain_id.exists' => 'Choose a valid domain.',
            'alias.min' => 'Aliases must be at least 3 characters.',
            'alias.max' => 'Aliases must be 50 characters or fewer.',
            'alias.regex' => 'Aliases can use letters, numbers, and dashes.',
            'password.min' => 'Passwords must be at least 6 characters.',
            'expires_at.after' => 'The expiration date must be in the future.',
        ];
    }
}
