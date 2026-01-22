<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
class DomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'hostname' => fake()->unique()->domainName(),
            'type' => \App\Models\Domain::TYPE_CUSTOM,
            'status' => \App\Models\Domain::STATUS_PENDING,
            'verification_method' => \App\Models\Domain::VERIFICATION_DNS,
            'verification_token' => fake()->regexify('[A-Za-z0-9]{32}'),
            'verified_at' => null,
        ];
    }

    public function platform(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'type' => \App\Models\Domain::TYPE_PLATFORM,
            'status' => \App\Models\Domain::STATUS_VERIFIED,
            'verification_method' => null,
            'verification_token' => null,
            'verified_at' => now(),
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => \App\Models\Domain::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);
    }
}
