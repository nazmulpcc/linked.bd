<?php

use App\Models\Domain;
use App\Models\Link;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

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

    $link->refresh();

    expect($link->click_count)->toBe(1)
        ->and($link->last_accessed_at)->not->toBeNull();
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

    $link->refresh();

    expect($link->click_count)->toBe(0)
        ->and($link->last_accessed_at)->toBeNull();
});

test('missing links return not found', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'missing.example.test',
    ]);

    $response = $this->get(sprintf('http://%s/nope', $domain->hostname));

    $response->assertNotFound();
});

test('password protected links show a prompt', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'secure.example.test',
    ]);
    Link::factory()->for($domain)->create([
        'code' => 'secure1',
        'password_hash' => Hash::make('secret'),
    ]);

    $response = $this->get(sprintf('http://%s/secure1', $domain->hostname));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('links/Password')
    );
});

test('password protected links redirect when unlocked', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'secure.example.test',
    ]);
    Link::factory()->for($domain)->create([
        'code' => 'secure2',
        'password_hash' => Hash::make('secret'),
        'destination_url' => 'https://example.com',
    ]);

    $response = $this
        ->from(sprintf('http://%s/secure2', $domain->hostname))
        ->post(sprintf('http://%s/secure2', $domain->hostname), [
            'password' => 'secret',
        ]);

    $response->assertRedirect('https://example.com');

    $link = Link::query()->where('code', 'secure2')->firstOrFail();

    expect($link->click_count)->toBe(1)
        ->and($link->last_accessed_at)->not->toBeNull();
});

test('password protected links reject invalid passwords', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'secure.example.test',
    ]);
    Link::factory()->for($domain)->create([
        'code' => 'secure3',
        'password_hash' => Hash::make('secret'),
    ]);

    $response = $this
        ->from(sprintf('http://%s/secure3', $domain->hostname))
        ->post(sprintf('http://%s/secure3', $domain->hostname), [
            'password' => 'wrong',
        ]);

    $response->assertSessionHasErrors('password');

    $link = Link::query()->where('code', 'secure3')->firstOrFail();

    expect($link->click_count)->toBe(0)
        ->and($link->last_accessed_at)->toBeNull();
});
