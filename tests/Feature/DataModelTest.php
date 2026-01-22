<?php

use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkAccessToken;
use Database\Seeders\PlatformDomainSeeder;
use Illuminate\Database\QueryException;

test('platform domain seeder uses the app url host', function () {
    config()->set('app.url', 'https://go.example.com/something');

    $this->seed(PlatformDomainSeeder::class);

    $domain = Domain::query()->first();

    expect($domain)
        ->not->toBeNull()
        ->and($domain->hostname)->toBe('go.example.com')
        ->and($domain->type)->toBe('platform')
        ->and($domain->status)->toBe('verified');
});

test('links enforce alias uniqueness per domain', function () {
    $domain = Domain::factory()->verified()->create();

    Link::factory()
        ->for($domain)
        ->withAlias('launch')
        ->create();

    expect(function () use ($domain) {
        Link::factory()
            ->for($domain)
            ->withAlias('launch')
            ->create();
    })->toThrow(QueryException::class);
});

test('link access tokens belong to links', function () {
    $link = Link::factory()->create();
    $token = LinkAccessToken::factory()->for($link)->create();

    expect($token->link_id)->toBe($link->id);
});
