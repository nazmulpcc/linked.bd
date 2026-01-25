<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\Request;

class UserResponse extends ApiResponse
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'created_at' => optional($user->created_at)->toIso8601String(),
        ];
    }
}
