<?php

namespace App\Http\Responses;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse extends JsonResource
{
    protected ?string $message = null;

    public function message(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function withMessage(string $message): static
    {
        return $this->message($message);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, string>
     */
    public function with($request): array
    {
        return array_filter([
            'message' => $this->message,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
