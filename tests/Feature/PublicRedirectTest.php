<?php

use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkRule;
use App\Models\LinkRuleCondition;
use App\Services\IpCountryResolver;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function dynamicDomain(string $hostname): Domain
{
    return Domain::factory()->platform()->create([
        'hostname' => $hostname,
    ]);
}

function dynamicLink(Domain $domain, string $code, string $fallback = 'https://example.com/fallback'): Link
{
    return Link::factory()->for($domain)->create([
        'code' => $code,
        'link_type' => 'dynamic',
        'destination_url' => $fallback,
        'fallback_destination_url' => $fallback,
    ]);
}

function addRule(Link $link, int $priority, string $destination, bool $enabled = true): LinkRule
{
    return LinkRule::factory()->for($link)->create([
        'priority' => $priority,
        'destination_url' => $destination,
        'enabled' => $enabled,
    ]);
}

function addCondition(LinkRule $rule, string $type, string $operator, mixed $value = null): void
{
    LinkRuleCondition::factory()->for($rule, 'rule')->create([
        'condition_type' => $type,
        'operator' => $operator,
        'value' => $value,
    ]);
}

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

test('dynamic links resolve matching rules before fallback', function () {
    app()->instance(IpCountryResolver::class, new class extends IpCountryResolver
    {
        public function resolve(string $ip): ?string
        {
            return 'US';
        }
    });

    $domain = dynamicDomain('dyn.example.test');
    $link = dynamicLink($domain, 'dyn001');
    $rule = addRule($link, 1, 'https://example.com/us');
    addCondition($rule, 'country', 'equals', 'US');

    $response = $this->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertRedirect('https://example.com/us');
});

test('dynamic links fall back when no rule matches', function () {
    app()->instance(IpCountryResolver::class, new class extends IpCountryResolver
    {
        public function resolve(string $ip): ?string
        {
            return 'CA';
        }
    });

    $domain = dynamicDomain('dyn-fallback.example.test');
    $link = dynamicLink($domain, 'dyn002');
    $rule = addRule($link, 1, 'https://example.com/us');
    addCondition($rule, 'country', 'equals', 'US');

    $response = $this->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertRedirect('https://example.com/fallback');
});

test('dynamic rules match country and device together', function () {
    app()->instance(IpCountryResolver::class, new class extends IpCountryResolver
    {
        public function resolve(string $ip): ?string
        {
            return 'US';
        }
    });

    $domain = dynamicDomain('dyn-device.example.test');
    $link = dynamicLink($domain, 'dyn003');
    $rule = addRule($link, 1, 'https://example.com/mobile-us');
    addCondition($rule, 'country', 'equals', 'US');
    addCondition($rule, 'device_type', 'equals', 'mobile');

    $response = $this
        ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)')
        ->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertRedirect('https://example.com/mobile-us');
});

test('dynamic rules can match browser splits', function () {
    $domain = dynamicDomain('dyn-browser.example.test');
    $link = dynamicLink($domain, 'dyn004');
    $rule = addRule($link, 1, 'https://example.com/safari');
    addCondition($rule, 'browser', 'equals', 'safari');

    $response = $this
        ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.2 Mobile/15E148 Safari/604.1')
        ->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertRedirect('https://example.com/safari');
});

test('dynamic rules can match utm campaigns', function () {
    $domain = dynamicDomain('dyn-utm.example.test');
    $link = dynamicLink($domain, 'dyn005');
    $rule = addRule($link, 1, 'https://example.com/campaign');
    addCondition($rule, 'utm_campaign', 'equals', 'spring-launch');

    $response = $this->get(sprintf(
        'http://%s/%s?utm_campaign=spring-launch',
        $domain->hostname,
        $link->code,
    ));

    $response->assertRedirect('https://example.com/campaign');
});

test('dynamic rules can match referrer presence', function () {
    $domain = dynamicDomain('dyn-referrer.example.test');
    $link = dynamicLink($domain, 'dyn006');
    $rule = addRule($link, 1, 'https://example.com/referrer');
    addCondition($rule, 'referrer_domain', 'exists');

    $response = $this
        ->withHeader('Referer', 'https://news.example/path')
        ->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertRedirect('https://example.com/referrer');
});

test('dynamic rules can match missing referrer', function () {
    $domain = dynamicDomain('dyn-referrer-missing.example.test');
    $link = dynamicLink($domain, 'dyn007');
    $rule = addRule($link, 1, 'https://example.com/direct');
    addCondition($rule, 'referrer_domain', 'not_exists');

    $response = $this->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertRedirect('https://example.com/direct');
});

test('dynamic rules pick the first match by priority', function () {
    app()->instance(IpCountryResolver::class, new class extends IpCountryResolver
    {
        public function resolve(string $ip): ?string
        {
            return 'US';
        }
    });

    $domain = dynamicDomain('dyn-priority.example.test');
    $link = dynamicLink($domain, 'dyn008');
    $ruleOne = addRule($link, 1, 'https://example.com/first');
    addCondition($ruleOne, 'country', 'equals', 'US');
    $ruleTwo = addRule($link, 2, 'https://example.com/second');
    addCondition($ruleTwo, 'country', 'equals', 'US');

    $response = $this->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertRedirect('https://example.com/first');
});

test('dynamic rules skip disabled matches', function () {
    app()->instance(IpCountryResolver::class, new class extends IpCountryResolver
    {
        public function resolve(string $ip): ?string
        {
            return 'US';
        }
    });

    $domain = dynamicDomain('dyn-disabled.example.test');
    $link = dynamicLink($domain, 'dyn009');
    $ruleOne = addRule($link, 1, 'https://example.com/disabled', false);
    addCondition($ruleOne, 'country', 'equals', 'US');
    $ruleTwo = addRule($link, 2, 'https://example.com/enabled');
    addCondition($ruleTwo, 'country', 'equals', 'US');

    $response = $this->get(sprintf('http://%s/%s', $domain->hostname, $link->code));

    $response->assertRedirect('https://example.com/enabled');
});
