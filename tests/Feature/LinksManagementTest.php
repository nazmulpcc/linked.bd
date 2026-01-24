<?php

use App\Jobs\GenerateQrForLink;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkRule;
use App\Models\LinkRuleCondition;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('users can view their links list', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    Link::factory()->for($domain)->for($user)->create();

    $response = $this->actingAs($user)->get(route('links.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('links/Index')
        ->has('links.data', 1)
    );
});

test('users cannot delete links they do not own', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    $link = Link::factory()->for($domain)->for($user)->create();

    $response = $this->actingAs($other)->delete(route('links.destroy', $link));

    $response->assertNotFound();
});

test('users can delete their own links', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    $link = Link::factory()->for($domain)->for($user)->create();

    $response = $this->actingAs($user)->delete(route('links.destroy', $link));

    $response->assertRedirect(route('links.index'));
    expect(Link::query()->whereKey($link->id)->exists())->toBeFalse();
});

test('deleting a link removes its qr asset', function () {
    Storage::fake('qr_code');

    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    $link = Link::factory()->for($domain)->for($user)->create([
        'qr_path' => 'links/remove.svg',
    ]);

    Storage::disk('qr_code')->put($link->qr_path, '<svg></svg>');

    $response = $this->actingAs($user)->delete(route('links.destroy', $link));

    $response->assertRedirect(route('links.index'));
    Storage::disk('qr_code')->assertMissing('links/remove.svg');
});

test('users can update dynamic link rules', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    $link = Link::factory()->for($domain)->for($user)->create([
        'link_type' => 'dynamic',
        'destination_url' => 'https://example.com/fallback',
        'fallback_destination_url' => 'https://example.com/fallback',
    ]);

    $rule = LinkRule::factory()->for($link)->create([
        'priority' => 1,
        'destination_url' => 'https://example.com/us',
    ]);
    LinkRuleCondition::factory()->for($rule, 'rule')->create([
        'condition_type' => 'country',
        'operator' => 'equals',
        'value' => 'US',
    ]);

    $payload = [
        'fallback_destination_url' => 'https://example.com/new-fallback',
        'rules' => [
            [
                'priority' => 1,
                'destination_url' => 'https://example.com/ca',
                'enabled' => true,
                'conditions' => [
                    [
                        'condition_type' => 'country',
                        'operator' => 'equals',
                        'value' => 'CA',
                    ],
                ],
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->patch(route('links.dynamic.update', $link), $payload);

    $response->assertSessionHas('success');

    $link->refresh();

    expect($link->fallback_destination_url)->toBe('https://example.com/new-fallback')
        ->and($link->rules)->toHaveCount(1)
        ->and($link->rules->first()->destination_url)->toBe('https://example.com/ca');
});

test('users can clone dynamic links', function () {
    Queue::fake();

    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    $link = Link::factory()->for($domain)->for($user)->create([
        'link_type' => 'dynamic',
        'destination_url' => 'https://example.com/fallback',
        'fallback_destination_url' => 'https://example.com/fallback',
    ]);

    $rule = LinkRule::factory()->for($link)->create([
        'priority' => 1,
        'destination_url' => 'https://example.com/us',
    ]);
    LinkRuleCondition::factory()->for($rule, 'rule')->create([
        'condition_type' => 'country',
        'operator' => 'equals',
        'value' => 'US',
    ]);

    $response = $this->actingAs($user)->post(route('links.clone', $link));

    $response->assertRedirect();

    $clone = Link::query()->where('id', '!=', $link->id)->latest('id')->firstOrFail();

    expect($clone->link_type->value)->toBe('dynamic')
        ->and($clone->fallback_destination_url)->toBe($link->fallback_destination_url)
        ->and($clone->rules)->toHaveCount(1);

    Queue::assertPushed(
        GenerateQrForLink::class,
        fn (GenerateQrForLink $job) => $job->linkId === $clone->id,
    );
});
