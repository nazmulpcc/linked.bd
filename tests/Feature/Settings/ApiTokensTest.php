<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('shows the api tokens page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/api-tokens');

    $response->assertOk();
});

it('creates an api token', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/api-tokens', [
        'name' => 'CLI token',
        'abilities' => [config('api.abilities.links.read')],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('new_api_token');

    $this->assertDatabaseHas('personal_access_tokens', [
        'name' => 'CLI token',
        'tokenable_id' => $user->id,
        'tokenable_type' => $user::class,
    ]);
});

it('revokes an api token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Revoke me', [
        config('api.abilities.links.read'),
    ]);
    $tokenId = $token->accessToken->id;

    $response = $this->actingAs($user)->delete("/settings/api-tokens/{$tokenId}");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(PersonalAccessToken::query()->whereKey($tokenId)->exists())->toBeFalse();
});
