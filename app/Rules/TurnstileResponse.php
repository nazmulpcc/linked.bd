<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class TurnstileResponse implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');

        if (! is_string($secret) || $secret === '') {
            $fail('Turnstile is not configured.');

            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Complete the Turnstile challenge to continue.');

            return;
        }

        $response = Http::asForm()->post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'secret' => $secret,
                'response' => $value,
                'remoteip' => request()->ip(),
            ],
        );

        if (! $response->ok() || $response->json('success') !== true) {
            $fail('Turnstile verification failed. Please try again.');
        }
    }
}
