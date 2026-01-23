<?php

use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkVisit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows dynamic stats and recent links', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    $customDomain = Domain::factory()->verified()->create([
        'user_id' => $user->id,
        'type' => Domain::TYPE_CUSTOM,
    ]);

    $activeLink = Link::factory()->for($domain)->for($user)->create([
        'password_hash' => 'hashed',
    ]);
    $expiredLink = Link::factory()->for($domain)->for($user)->create([
        'expires_at' => now()->subDay(),
    ]);

    LinkVisit::factory()->for($activeLink)->create([
        'visited_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('stats.clicks_today', 1)
        ->where('stats.active_links', 1)
        ->where('stats.custom_domains', 1)
        ->where('stats.protected_links', 1)
        ->has('recent_links', 2)
    );
});
