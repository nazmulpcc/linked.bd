<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiErrorResponse extends JsonResponse
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public static function make(string $message, array $errors = [], int $status = 400): self
    {
        return new self([
            'message' => $message,
            'errors' => (object) $errors,
        ], $status);
    }
}
