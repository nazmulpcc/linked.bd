<?php

use App\Jobs\GenerateQrForLink;
use App\Models\Domain;
use App\Models\Link;
use App\Models\LinkAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('qr_code');
});

test('qr job stores an svg and updates the link', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'qr.example.test',
    ]);
    $link = Link::factory()->for($domain)->create([
        'code' => 'qrtest1',
        'qr_path' => null,
    ]);

    GenerateQrForLink::dispatchSync($link->id);

    $link->refresh();

    expect($link->qr_path)->not->toBeNull();

    Storage::disk('qr_code')->assertExists($link->qr_path);
});

test('guests can download their qr code using access tokens', function () {
    $domain = Domain::factory()->platform()->create([
        'hostname' => 'qr.example.test',
    ]);
    $link = Link::factory()->for($domain)->create([
        'code' => 'qrtest2',
        'qr_path' => 'links/qrtest2.svg',
    ]);
    $token = LinkAccessToken::factory()->for($link)->create();

    Storage::disk('qr_code')->put($link->qr_path, '<svg></svg>');

    $response = $this->get(route('links.qr.guest', ['token' => $token->token]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/svg+xml');
});

test('authenticated owners can download qr codes', function () {
    $user = User::factory()->create();
    $domain = Domain::factory()->for($user)->verified()->create([
        'hostname' => 'qr.example.test',
    ]);
    $link = Link::factory()->for($domain)->create([
        'user_id' => $user->id,
        'qr_path' => 'links/qrtest3.svg',
    ]);

    Storage::disk('qr_code')->put($link->qr_path, '<svg></svg>');

    $response = actingAs($user)->get(route('links.qr.download', ['link' => $link->id]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/svg+xml');
});
