<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domains = collect(explode(',', (string) config('links.system_domains')))
            ->map(fn (string $domain) => Str::lower(trim($domain)))
            ->filter()
            ->unique()
            ->values();

        if ($domains->isEmpty()) {
            return;
        }

        $domains->each(function (string $hostname) {
            Domain::query()->updateOrCreate(
                ['hostname' => $hostname],
                [
                    'user_id' => null,
                    'type' => Domain::TYPE_PLATFORM,
                    'status' => Domain::STATUS_VERIFIED,
                    'verification_method' => null,
                    'verification_token' => null,
                    'verified_at' => now(),
                ],
            );
        });
    }
}
