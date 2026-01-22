<?php

namespace App\Http\Requests\Domains;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreDomainRequest extends FormRequest
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
            'hostname' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.(?!-)[A-Za-z0-9-]{1,63}(?<!-))*$/',
                Rule::unique('domains', 'hostname'),
                Rule::notIn(Arr::wrap($this->platformHostname())),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'hostname.required' => 'Enter a domain hostname to verify.',
            'hostname.string' => 'The hostname must be a valid text value.',
            'hostname.max' => 'The hostname may not exceed 255 characters.',
            'hostname.regex' => 'Enter a valid hostname without paths or protocols.',
            'hostname.unique' => 'This hostname is already connected to an account.',
            'hostname.not_in' => 'This hostname is reserved for platform links.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $hostname = $this->input('hostname');

        if (! is_string($hostname)) {
            return;
        }

        $hostname = trim($hostname);

        if ($hostname === '') {
            return;
        }

        $normalized = $this->normalizeHostname($hostname);

        $this->merge([
            'hostname' => $normalized,
        ]);
    }

    private function normalizeHostname(string $hostname): string
    {
        if (Str::contains($hostname, '://')) {
            $host = parse_url($hostname, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                return Str::lower($host);
            }
        }

        $host = parse_url('https://'.$hostname, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return Str::lower($host);
        }

        return Str::lower($hostname);
    }

    private function platformHostname(): ?string
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return Str::lower($host);
        }

        return null;
    }
}
