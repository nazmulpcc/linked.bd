<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBulkImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'urls' => ['required'],
            'domain_id' => ['required', 'integer', Rule::exists('domains', 'id')],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'deduplicate' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'urls.required' => 'Provide at least one URL.',
            'domain_id.required' => 'Choose a domain.',
            'domain_id.exists' => 'Choose a valid domain.',
            'password.min' => 'Passwords must be at least 6 characters.',
            'expires_at.after' => 'The expiration date must be in the future.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $urls = $this->normalizedUrls();

            if ($urls === []) {
                $validator->errors()->add('urls', 'Provide at least one valid URL.');

                return;
            }

            $invalid = array_filter($urls, fn (string $url) => ! $this->isValidUrl($url));

            if ($invalid !== []) {
                $validator->errors()->add('urls', 'One or more URLs are invalid.');
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function normalizedUrls(): array
    {
        $raw = $this->input('urls');

        if (is_string($raw)) {
            $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
            $urls = array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));
        } elseif (is_array($raw)) {
            $urls = array_values(array_filter(array_map(function ($value): string {
                if (! is_string($value)) {
                    return '';
                }

                return trim($value);
            }, $raw), fn (string $line) => $line !== ''));
        } else {
            $urls = [];
        }

        if ($this->boolean('deduplicate')) {
            $urls = array_values(array_unique($urls));
        }

        return $urls;
    }

    private function isValidUrl(string $url): bool
    {
        if (! preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
