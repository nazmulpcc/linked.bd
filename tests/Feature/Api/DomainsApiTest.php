<?php

use App\Models\Domain;
use App\Models\User;

it('creates and deletes domains via the api', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api', [
        config('api.abilities.domains.read'),
        config('api.abilities.domains.write'),
    ])->plainTextToken;

    $response = $this->postJson('/api/v1/domains', [
        'hostname' => 'api-test.example.com',
    ], [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertCreated();
    $domainId = $response->json('data.id');

    $this->assertDatabaseHas('domains', [
        'id' => $domainId,
        'user_id' => $user->id,
    ]);

    $delete = $this->deleteJson("/api/v1/domains/{$domainId}", [], [
        'Authorization' => 'Bearer '.$token,
    ]);

    $delete->assertOk();
    $this->assertDatabaseMissing('domains', [
        'id' => $domainId,
    ]);
});

it('lists domains for the authenticated user', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->create([
        'user_id' => $user->id,
    ]);

    $token = $user->createToken('api', [
        config('api.abilities.domains.read'),
    ])->plainTextToken;

    $response = $this->getJson('/api/v1/domains', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('id')->all())
        ->toContain($domain->id);
});
