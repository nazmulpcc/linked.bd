<?php

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;

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
