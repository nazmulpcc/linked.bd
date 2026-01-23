<?php

namespace App\Http\Requests\Links;

use App\Rules\TurnstileResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
                Rule::excludeIf($this->user() === null),
                'date',
                'after:now',
            ],
            'cf-turnstile-response' => [
                'required',
                new TurnstileResponse,
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
            'cf-turnstile-response.required' => 'Complete the Turnstile challenge to continue.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $url = $this->string('destination_url')->toString();

            if ($url === '') {
                return;
            }

            $host = parse_url($url, PHP_URL_HOST);

            if (! is_string($host) || $host === '') {
                return;
            }

            if ($this->isBlockedIp($host)) {
                $validator->errors()->add('destination_url', 'Destination URLs cannot target private or reserved IPs.');
            }
        });
    }

    private function isBlockedIp(string $host): bool
    {
        if (! filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return ! filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }
}
