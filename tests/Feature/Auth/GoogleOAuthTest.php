<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('it redirects to google with a stored state', function () {
    config()->set('services.google.client_id', 'google-client-id');

    $response = $this->get(route('oauth.google.redirect'));

    $response->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth');
    $response->assertSessionHas('google_oauth_state');
});

test('it rejects callback requests with missing state', function () {
    $response = $this->get(route('oauth.google.callback', [
        'code' => 'auth-code',
    ]));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
});

test('it signs the user in with a valid google callback', function () {
    config()->set('services.google.client_id', 'google-client-id');
    config()->set('services.google.client_secret', 'google-client-secret');

    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'access-token',
        ], 200),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'sub' => 'google-id',
            'email' => 'mila@example.com',
            'name' => 'Mila Harper',
            'picture' => 'https://example.com/avatar.png',
            'email_verified' => true,
        ], 200),
    ]);

    $response = $this->withSession([
        'google_oauth_state' => 'state-token',
    ])->get(route('oauth.google.callback', [
        'state' => 'state-token',
        'code' => 'auth-code',
    ]));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $user = User::first();

    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('mila@example.com')
        ->and($user->oauth_provider)->toBe('google')
        ->and($user->oauth_provider_id)->toBe('google-id')
        ->and($user->avatar)->toBe('https://example.com/avatar.png')
        ->and($user->email_verified_at)->not->toBeNull();
});
