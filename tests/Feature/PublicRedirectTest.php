<?php

use App\Models\Domain;
use App\Models\Link;

test('platform domain links resolve by code', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'go.example.test',
    ]);
    $link = Link::factory()->for($domain)->create([
        'code' => 'abc1234',
        'destination_url' => 'https://example.com',
    ]);

    $response = $this->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertRedirect('https://example.com');
});

test('custom domain links resolve by alias', function () {
    $domain = Domain::factory()->verified()->create([
        'hostname' => 'custom.example.test',
    ]);
    $link = Link::factory()
        ->for($domain)
        ->withAlias('launch')
        ->create([
            'code' => 'unused42',
            'destination_url' => 'https://laravel.com',
        ]);

    $response = $this->get(sprintf('http://%s/%s', $domain->hostname, $link->alias));

    $response->assertRedirect('https://laravel.com');
});

test('unverified custom domains return not found', function () {
    $domain = Domain::factory()->create([
        'hostname' => 'pending.example.test',
        'status' => Domain::STATUS_PENDING,
    ]);
    $link = Link::factory()
        ->for($domain)
        ->withAlias('launch')
        ->create();

    $response = $this->get(sprintf('http://%s/%s', $domain->hostname, $link->alias));

    $response->assertNotFound();
});

test('expired links return gone', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'expired.example.test',
    ]);
    $link = Link::factory()->for($domain)->create([
        'code' => 'expired1',
        'expires_at' => now()->subDay(),
        'destination_url' => 'https://example.com',
    ]);

    $response = $this->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertStatus(410);
});

test('missing links return not found', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'missing.example.test',
    ]);

    $response = $this->get(sprintf('http://%s/nope', $domain->hostname));

    $response->assertNotFound();
});
