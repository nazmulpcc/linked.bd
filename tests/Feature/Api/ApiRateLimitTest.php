<?php

use App\Models\Domain;
use App\Models\User;

it('throttles api requests per token', function () {
    config()->set('api.rate_limits.api_per_token', 1);

    $user = User::factory()->create();
    $token = $user->createToken('api', [
        config('api.abilities.links.read'),
    ])->plainTextToken;

    $response = $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk();

    $secondResponse = $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $secondResponse->assertStatus(429);
});

it('throttles bulk submissions per token', function () {
    config()->set('api.rate_limits.api_per_token', 120);
    config()->set('api.rate_limits.bulk_per_token', 1);

    $user = User::factory()->create();
    $domain = Domain::factory()->verified()->create([
        'user_id' => $user->id,
    ]);

    $token = $user->createToken('api', [
        config('api.abilities.bulk.read'),
        config('api.abilities.bulk.write'),
    ])->plainTextToken;

    $response = $this->postJson('/api/v1/bulk-imports', [
        'domain_id' => $domain->id,
        'urls' => [
            'https://example.com',
        ],
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertCreated();

    $secondResponse = $this->postJson('/api/v1/bulk-imports', [
        'domain_id' => $domain->id,
        'urls' => [
            'https://laravel.com',
        ],
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);

    $secondResponse->assertStatus(429);
});
