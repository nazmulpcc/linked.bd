<?php

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;

it('creates a link via the api', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    $token = $user->createToken('api', [
        config('api.abilities.links.read'),
        config('api.abilities.links.write'),
    ])->plainTextToken;

    $response = $this->postJson('/api/v1/links', [
        'domain_id' => $domain->id,
        'destination_url' => 'https://example.com',
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertCreated();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'domain',
            'short_url',
            'destination_url',
            'link_type',
        ],
        'message',
    ]);

    $this->assertDatabaseHas('links', [
        'domain_id' => $domain->id,
        'user_id' => $user->id,
    ]);
});

it('lists links for the authenticated user', function () {
    $user = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $user->id,
        'domain_id' => Domain::factory()->platform()->create()->id,
    ]);

    $token = $user->createToken('api', [
        config('api.abilities.links.read'),
    ])->plainTextToken;

    $response = $this->getJson('/api/v1/links', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data',
        'links',
        'meta',
    ]);

    expect(collect($response->json('data'))->pluck('id')->all())
        ->toContain($link->ulid);
});

it('returns qr status for a link', function () {
    $user = User::factory()->create();
    $link = Link::factory()->create([
        'user_id' => $user->id,
        'domain_id' => Domain::factory()->platform()->create()->id,
    ]);

    $token = $user->createToken('api', [
        config('api.abilities.links.read'),
    ])->plainTextToken;

    $response = $this->getJson("/api/v1/links/{$link->ulid}/qr", [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.status', 'pending');
});
