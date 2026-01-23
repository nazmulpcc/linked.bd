<?php

use App\Jobs\RecordLinkClick;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkVisit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('users can view analytics for their links', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    $link = Link::factory()->for($domain)->for($user)->create();

    LinkVisit::factory()->for($link)->create([
        'referrer_host' => 'example.com',
        'device_type' => 'desktop',
        'browser' => 'chrome',
        'country_code' => 'US',
        'visited_at' => now()->subDay(),
    ]);
    LinkVisit::factory()->for($link)->create([
        'referrer_host' => 'twitter.com',
        'device_type' => 'mobile',
        'browser' => 'safari',
        'country_code' => 'CA',
        'visited_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('links.show', $link));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('links/Show')
        ->where('link.id', $link->id)
        ->has('analytics.visits_by_day')
        ->has('analytics.top_referrers')
        ->has('analytics.device_breakdown')
        ->has('analytics.browser_breakdown')
        ->has('analytics.country_breakdown')
    );
});

test('recording link visits stores analytics data', function () {
    $domain = Domain::factory()->platform()->create();
    $link = Link::factory()->for($domain)->create([
        'click_count' => 0,
    ]);

    $visitData = [
        'visited_at' => now()->toDateTimeString(),
        'referrer_host' => 'news.example',
        'device_type' => 'desktop',
        'browser' => 'firefox',
        'country_code' => 'GB',
        'user_agent' => 'Mozilla/5.0',
    ];

    dispatch_sync(new RecordLinkClick($link->id, $visitData));

    $link->refresh();

    expect($link->click_count)->toBe(1)
        ->and($link->last_accessed_at)->not->toBeNull();

    $visit = LinkVisit::query()->where('link_id', $link->id)->first();

    expect($visit)->not->toBeNull()
        ->and($visit->referrer_host)->toBe('news.example')
        ->and($visit->device_type)->toBe('desktop')
        ->and($visit->browser)->toBe('firefox')
        ->and($visit->country_code)->toBe('GB')
        ->and($visit->user_agent)->toBe('Mozilla/5.0');
});
