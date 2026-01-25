<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserResponse;
use App\Models\User;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): UserResponse
    {
        /** @var User $user */
        $user = $request->user();

        return (new UserResponse($user))->withMessage('Authenticated.');
    }
}
