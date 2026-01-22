<?php

use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkAccessToken;
use App\Models\User;

test('guests can create links on platform domains', function () {
    $domain = Domain::factory()->platform()->create();

    $response = $this->post(route('links.store'), [
        'destination_url' => 'https://example.com',
        'domain_id' => $domain->id,
    ]);

    $response->assertRedirectContains('/links/success/');

    $link = Link::query()->first();

    expect($link)
        ->not->toBeNull()
        ->and($link->user_id)->toBeNull()
        ->and($link->expires_at)->not->toBeNull();
});

test('password is optional when creating links', function () {
    $domain = Domain::factory()->platform()->create();

    $response = $this->post(route('links.store'), [
        'destination_url' => 'https://example.com',
        'domain_id' => $domain->id,
        'password' => '',
    ]);

    $response->assertRedirectContains('/links/success/');
});

test('authenticated users can create custom domain links with aliases', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->verified()->create();

    $response = $this->actingAs($user)->post(route('links.store'), [
        'destination_url' => 'https://example.com',
        'domain_id' => $domain->id,
        'alias' => 'launch',
    ]);

    $response->assertRedirectContains('/links/success/');

    $link = Link::query()->first();

    expect($link)
        ->not->toBeNull()
        ->and($link->alias)->toBe('launch')
        ->and($link->domain_id)->toBe($domain->id);
});

test('custom domains must be verified', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->create([
        'status' => Domain::STATUS_PENDING,
    ]);

    $response = $this->actingAs($user)->post(route('links.store'), [
        'destination_url' => 'https://example.com',
        'domain_id' => $domain->id,
    ]);

    $response->assertSessionHasErrors('domain_id');
});

test('platform domains reject aliases', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();

    $response = $this->actingAs($user)->post(route('links.store'), [
        'destination_url' => 'https://example.com',
        'domain_id' => $domain->id,
        'alias' => 'promo',
    ]);

    $response->assertSessionHasErrors('alias');
});

test('success page renders for access tokens', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'go.example.com',
    ]);
    $link = Link::factory()->for($domain)->create([
        'destination_url' => 'https://example.com',
        'alias' => 'launch',
    ]);
    $token = LinkAccessToken::factory()->for($link)->create();

    $response = $this->get(route('links.success', ['token' => $token->token]));

    $response->assertOk();
    $response->assertSee('go.example.com');
});
