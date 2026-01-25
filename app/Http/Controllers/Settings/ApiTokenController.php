<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ApiTokenDestroyRequest;
use App\Http\Requests\Settings\ApiTokenStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    /**
     * Show the API tokens settings page.
     */
    public function index(Request $request): Response
    {
        $abilities = collect(config('api.abilities'))
            ->map(function (array $group, string $groupKey): array {
                return [
                    'label' => Str::headline($groupKey),
                    'abilities' => collect($group)
                        ->map(function (string $ability): array {
                            return [
                                'value' => $ability,
                                'label' => Str::headline(str_replace(':', ' ', $ability)),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $tokens = $request->user()
            ->tokens()
            ->latest()
            ->get()
            ->map(function (PersonalAccessToken $token): array {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities ?? [],
                    'last_used_at' => optional($token->last_used_at)->toIso8601String(),
                    'created_at' => optional($token->created_at)->toIso8601String(),
                ];
            })
            ->all();

        return Inertia::render('settings/ApiTokens', [
            'abilities' => $abilities,
            'tokens' => $tokens,
            'newToken' => $request->session()->pull('new_api_token'),
        ]);
    }

    /**
     * Store a new API token.
     */
    public function store(ApiTokenStoreRequest $request): RedirectResponse
    {
        $token = $request->user()->createToken(
            $request->validated('name'),
            $request->validated('abilities', [])
        );

        return back()
            ->with('success', 'API token created.')
            ->with('new_api_token', $token->plainTextToken);
    }

    /**
     * Revoke an API token.
     */
    public function destroy(ApiTokenDestroyRequest $request, PersonalAccessToken $token): RedirectResponse
    {
        $token->delete();

        return back()->with('success', 'API token revoked.');
    }
}
