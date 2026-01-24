<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkRule;
use App\Models\LinkRuleCondition;
use App\Services\LinkRedirectResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

test('dynamic rules are cached when enabled', function () {
    config([
        'links.dynamic.cache.enabled' => true,
        'links.dynamic.cache.ttl_seconds' => 300,
    ]);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());

    $domain = Domain::factory()->platform()->create();
    $link = Link::factory()->for($domain)->create([
        'link_type' => 'dynamic',
        'fallback_destination_url' => 'https://example.com/fallback',
        'destination_url' => 'https://example.com/fallback',
    ]);

    $rule = LinkRule::factory()->for($link)->create([
        'priority' => 1,
        'destination_url' => 'https://example.com/mobile',
    ]);
    LinkRuleCondition::factory()->for($rule, 'rule')->create([
        'condition_type' => 'device_type',
        'operator' => 'equals',
        'value' => 'mobile',
    ]);

    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    $resolver = app(LinkRedirectResolver::class);
    $result = $resolver->resolveWithRule($link, $request);

    expect($result['destination_url'])->toBe('https://example.com/mobile');
});

test('dynamic rules skip cache when disabled', function () {
    config(['links.dynamic.cache.enabled' => false]);

    Cache::shouldReceive('remember')->never();

    $domain = Domain::factory()->platform()->create();
    $link = Link::factory()->for($domain)->create([
        'link_type' => 'dynamic',
        'fallback_destination_url' => 'https://example.com/fallback',
        'destination_url' => 'https://example.com/fallback',
    ]);

    $rule = LinkRule::factory()->for($link)->create([
        'priority' => 1,
        'destination_url' => 'https://example.com/mobile',
    ]);
    LinkRuleCondition::factory()->for($rule, 'rule')->create([
        'condition_type' => 'device_type',
        'operator' => 'equals',
        'value' => 'mobile',
    ]);

    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
        'REMOTE_ADDR' => '127.0.0.1',
    ]);

    $resolver = app(LinkRedirectResolver::class);
    $result = $resolver->resolveWithRule($link, $request);

    expect($result['destination_url'])->toBe('https://example.com/mobile');
});
