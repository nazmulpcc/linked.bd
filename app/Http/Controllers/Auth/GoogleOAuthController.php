<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleOAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);

        $request->session()->put('google_oauth_state', $state);

        $query = Arr::query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('oauth.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = $request->input('state');
        $expectedState = $request->session()->pull('google_oauth_state');

        if (! $state || ! $expectedState || ! hash_equals($expectedState, $state)) {
            return redirect()->route('login')->with('error', 'Unable to verify the Google login request.');
        }

        $code = $request->input('code');

        if (! $code) {
            return redirect()->route('login')->with('error', 'Google did not return a valid authorization code.');
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('oauth.google.callback'),
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        if (! $tokenResponse->successful()) {
            return redirect()->route('login')->with('error', 'Unable to authenticate with Google.');
        }

        $accessToken = $tokenResponse->json('access_token');

        if (! $accessToken) {
            return redirect()->route('login')->with('error', 'Google did not return an access token.');
        }

        $profileResponse = Http::withToken($accessToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $profileResponse->successful()) {
            return redirect()->route('login')->with('error', 'Unable to fetch your Google profile.');
        }

        $profile = $profileResponse->json();
        $providerId = $profile['sub'] ?? $profile['id'] ?? null;
        $email = $profile['email'] ?? null;

        if (! $providerId || ! $email) {
            return redirect()->route('login')->with('error', 'Google profile data was incomplete.');
        }

        $user = User::query()
            ->where('oauth_provider', 'google')
            ->where('oauth_provider_id', $providerId)
            ->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            $user = new User;
        }

        $payload = [
            'name' => $profile['name'] ?? $user->name ?? $email,
            'email' => $email,
            'avatar' => $profile['picture'] ?? $user->avatar,
            'oauth_provider' => 'google',
            'oauth_provider_id' => $providerId,
        ];

        if (! $user->exists) {
            $payload['password'] = Str::password(32);
        }

        if (($profile['email_verified'] ?? false) && ! $user->email_verified_at) {
            $payload['email_verified_at'] = now();
        }

        $user->forceFill($payload);
        $user->save();

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'));
    }
}
