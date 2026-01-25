<?php

use App\Actions\Domains\VerifyDomain;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;

test('users can add a custom domain', function () {
    config()->set('links.domain_verification_cname', 'verify.linked.test');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('domains.store'), [
        'hostname' => 'Go.Example.com',
    ]);

    $response->assertRedirect(route('domains.index'));

    $domain = Domain::query()->first();

    expect($domain)
        ->not->toBeNull()
        ->and($domain->hostname)->toBe('go.example.com')
        ->and($domain->status)->toBe(Domain::STATUS_PENDING)
        ->and($domain->verification_token)->toBe('verify.linked.test');
});

test('users cannot claim the platform hostname', function () {
    config()->set('app.url', 'https://go.example.com');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('domains.store'), [
        'hostname' => 'go.example.com',
    ]);

    $response->assertSessionHasErrors('hostname');
    expect(Domain::query()->count())->toBe(0);
});

test('users can verify domains when dns check passes', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->create([
        'status' => Domain::STATUS_PENDING,
        'verification_token' => 'token',
        'verification_method' => Domain::VERIFICATION_DNS,
    ]);

    app()->instance(VerifyDomain::class, new class extends VerifyDomain
    {
        public function verify(Domain $domain): array
        {
            return ['success' => true, 'message' => 'Domain verified.'];
        }
    });

    $response = $this->actingAs($user)->post(route('domains.verify', $domain));

    $response->assertRedirect(route('domains.index'));

    expect($domain->refresh()->status)->toBe(Domain::STATUS_VERIFIED);
});

test('domain verification fails when dns check fails', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->create([
        'status' => Domain::STATUS_PENDING,
        'verification_token' => 'token',
        'verification_method' => Domain::VERIFICATION_DNS,
    ]);

    app()->instance(VerifyDomain::class, new class extends VerifyDomain
    {
        public function verify(Domain $domain): array
        {
            return ['success' => false, 'message' => 'CNAME record does not point to the expected target.'];
        }
    });

    $response = $this->actingAs($user)->post(route('domains.verify', $domain));

    $response->assertSessionHas('error');
    expect($domain->refresh()->status)->toBe(Domain::STATUS_PENDING);
});

test('users can disable a domain', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->verified()->create();

    $response = $this->actingAs($user)->post(route('domains.disable', $domain));

    $response->assertRedirect(route('domains.index'));
    expect($domain->refresh()->status)->toBe(Domain::STATUS_DISABLED);
});

test('domains with links cannot be deleted', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->create();
    Link::factory()->for($domain)->for($user)->create();

    $response = $this->actingAs($user)->delete(route('domains.destroy', $domain));

    $response->assertSessionHas('error');
    expect(Domain::query()->whereKey($domain->id)->exists())->toBeTrue();
});

test('users cannot manage domains they do not own', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $domain = Domain::factory()->for($user)->create();

    $response = $this->actingAs($otherUser)->post(route('domains.verify', $domain));

    $response->assertNotFound();
});
