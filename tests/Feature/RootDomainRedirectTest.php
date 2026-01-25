<?php

use App\Jobs\RecordLinkClick;
use App\Models\Domain;
use App\Models\Link;
use Illuminate\Support\Facades\Queue;

test('redirects unknown root domains to the app url', function () {
    config(['app.url' => 'https://linked.test']);

    $response = $this->get('https://unknown.test/');

    $response->assertRedirect('https://linked.test');
});

test('returns not found when a verified domain has no redirection', function () {
    config(['app.url' => 'https://linked.test']);

    Domain::factory()->verified()->create([
        'hostname' => 'font.sh',
        'redirection_id' => null,
    ]);

    $response = $this->get('https://font.sh/');

    $response->assertNotFound();
});

test('redirects verified domains with a redirection link', function () {
    Queue::fake();
    config(['app.url' => 'https://linked.test']);

    $link = Link::factory()->create([
        'destination_url' => 'https://example.com',
    ]);

    Domain::factory()->verified()->create([
        'hostname' => 'font.sh',
        'redirection_id' => $link->id,
    ]);

    $response = $this->get('https://font.sh/');

    $response->assertRedirect('https://example.com');
    Queue::assertPushed(RecordLinkClick::class, fn (RecordLinkClick $job) => $job->linkId === $link->id);
});
