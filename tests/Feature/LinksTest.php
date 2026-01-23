<?php

use App\Jobs\GenerateQrForLink;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;

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

test('guest expiry always defaults to ttl', function () {
    $now = now();
    Date::setTestNow($now);
    $overrideExpiry = $now->addDays(30);
    $expectedExpiry = $now->addDays(config('links.guest_ttl_days'));

    $domain = Domain::factory()->platform()->create();

    $response = $this->post(route('links.store'), [
        'destination_url' => 'https://example.com',
        'domain_id' => $domain->id,
        'expires_at' => $overrideExpiry->toDateTimeString(),
    ]);

    $response->assertRedirectContains('/links/success/');

    $link = Link::query()->firstOrFail();

    expect($link->expires_at->diffInSeconds($expectedExpiry))->toBeLessThanOrEqual(1);

    Date::setTestNow();
});

test('destination urls cannot target private ips', function () {
    $domain = Domain::factory()->platform()->create();

    $response = $this->post(route('links.store'), [
        'destination_url' => 'http://127.0.0.1/admin',
        'domain_id' => $domain->id,
    ]);

    $response->assertSessionHasErrors('destination_url');
});

test('guest link creation is rate limited', function () {
    $domain = Domain::factory()->platform()->create();

    $response = null;

    for ($attempt = 0; $attempt < 11; $attempt++) {
        $response = $this->post(route('links.store'), [
            'destination_url' => 'https://example.com',
            'domain_id' => $domain->id,
        ]);
    }

    $response->assertStatus(429);
});

test('link creation queues qr generation', function () {
    Queue::fake();

    $domain = Domain::factory()->platform()->create();

    $response = $this->post(route('links.store'), [
        'destination_url' => 'https://example.com',
        'domain_id' => $domain->id,
    ]);

    $response->assertRedirectContains('/links/success/');

    Queue::assertPushed(GenerateQrForLink::class);
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
