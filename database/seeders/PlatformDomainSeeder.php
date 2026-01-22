<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appUrl = config('app.url');
        $host = parse_url($appUrl, PHP_URL_HOST);

        if (! $host && Str::contains($appUrl, '.')) {
            $host = parse_url('https://'.$appUrl, PHP_URL_HOST);
        }

        if (! $host) {
            return;
        }

        Domain::query()->updateOrCreate(
            ['hostname' => $host],
            [
                'user_id' => null,
                'type' => 'platform',
                'status' => 'verified',
                'verification_method' => null,
                'verification_token' => null,
                'verified_at' => now(),
            ],
        );
    }
}
