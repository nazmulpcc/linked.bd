<?php

use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Storage::fake('qr_code');
});

test('prune command deletes expired links and removes qr assets', function () {
    $domain = Domain::factory()->platform()->create();
    $user = User::factory()->create();

    $expired = Link::factory()->for($domain)->create([
        'user_id' => $user->id,
        'expires_at' => now()->subDay(),
        'qr_path' => 'links/expired.svg',
    ]);

    Storage::disk('qr_code')->put($expired->qr_path, '<svg></svg>');

    artisan('links:prune')->assertExitCode(0);

    expect(Link::query()->find($expired->id))->toBeNull();
    Storage::disk('qr_code')->assertMissing('links/expired.svg');
});

test('prune command deletes guest links past ttl', function () {
    config(['links.guest_ttl_days' => 3]);

    $domain = Domain::factory()->platform()->create();
    $guest = Link::factory()->for($domain)->create([
        'user_id' => null,
        'created_at' => now()->subDays(4),
        'qr_path' => 'links/guest.svg',
    ]);

    Storage::disk('qr_code')->put($guest->qr_path, '<svg></svg>');

    artisan('links:prune')->assertExitCode(0);

    expect(Link::query()->find($guest->id))->toBeNull();
    Storage::disk('qr_code')->assertMissing('links/guest.svg');
});

test('prune command keeps active links', function () {
    config(['links.guest_ttl_days' => 3]);

    $domain = Domain::factory()->platform()->create();
    $active = Link::factory()->for($domain)->create([
        'user_id' => null,
        'created_at' => now()->subDays(2),
        'expires_at' => now()->addDay(),
        'qr_path' => 'links/active.svg',
    ]);

    Storage::disk('qr_code')->put($active->qr_path, '<svg></svg>');

    artisan('links:prune')->assertExitCode(0);

    expect(Link::query()->find($active->id))->not->toBeNull();
    Storage::disk('qr_code')->assertExists('links/active.svg');
});
