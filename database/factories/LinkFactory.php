<?php

namespace Database\Factories;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'domain_id' => Domain::factory()->platform(),
            'user_id' => User::factory(),
            'code' => Str::lower(Str::random(7)),
            'alias' => null,
            'destination_url' => fake()->url(),
            'password_hash' => null,
            'expires_at' => null,
            'click_count' => 0,
            'last_accessed_at' => null,
            'qr_path' => null,
        ];
    }

    public function forCustomDomain(): static
    {
        return $this->state(fn () => [
            'domain_id' => Domain::factory()->verified(),
        ]);
    }

    public function withAlias(string $alias): static
    {
        return $this->state(fn () => [
            'alias' => $alias,
        ]);
    }
}
