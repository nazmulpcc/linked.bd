<?php

use App\Models\User;

it('requires authentication for api requests', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized();
    $response->assertJsonStructure([
        'message',
        'errors',
    ]);
});

it('returns the authenticated user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-test', [
        config('api.abilities.links.read'),
    ])->plainTextToken;

    $response = $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'email',
            'avatar',
            'created_at',
        ],
        'message',
    ]);
});

it('enforces link read ability', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-test', [
        config('api.abilities.domains.read'),
    ])->plainTextToken;

    $response = $this->getJson('/api/v1/links', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertForbidden();
    $response->assertJsonStructure([
        'message',
        'errors',
    ]);
});
