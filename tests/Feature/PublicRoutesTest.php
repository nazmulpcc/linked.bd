<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('renders the homepage', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('renders the public create link page', function () {
    $response = $this->get(route('links.create'));

    $response->assertOk();
});

test('guests are redirected away from the domains area', function () {
    $response = $this->get(route('domains.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can access the domains area', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('domains.index'));

    $response->assertOk();
});

test('unknown routes render the not found page', function () {
    $response = $this->get('/missing-link');

    $response->assertNotFound();
    $response->assertSee("We couldn't find that page.", false);
});

test('expired links render the gone page', function () {
    Route::get('/testing/expired', function () {
        abort(410);
    });

    $response = $this->get('/testing/expired');

    $response->assertStatus(410);
    $response->assertSee('This short link is no longer available.');
});
