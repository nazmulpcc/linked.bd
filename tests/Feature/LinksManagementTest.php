<?php

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('users can view their links list', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->platform()->create();
    Link::factory()->for($domain)->for($user)->create();

    $response = $this->actingAs($user)->get(route('links.index'));

    $response->assertOk();
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
