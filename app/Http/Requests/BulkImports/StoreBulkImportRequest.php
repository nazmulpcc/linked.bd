<?php

namespace App\Http\Requests\BulkImports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBulkImportRequest extends FormRequest
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
            'urls' => ['required', 'string'],
            'domain_id' => ['required', 'integer', Rule::exists('domains', 'id')],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'deduplicate' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'urls.required' => 'Paste at least one URL.',
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
                $validator->errors()->add('urls', 'Paste at least one valid URL.');

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

        if (! is_string($raw)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $urls = array_values(array_filter(array_map('trim', $lines), fn (string $line) => $line !== ''));

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
