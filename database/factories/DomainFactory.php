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
            'type' => 'custom',
            'status' => 'pending_verification',
            'verification_method' => 'dns_txt',
            'verification_token' => fake()->regexify('[A-Za-z0-9]{32}'),
            'verified_at' => null,
        ];
    }

    public function platform(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'type' => 'platform',
            'status' => 'verified',
            'verification_method' => null,
            'verification_token' => null,
            'verified_at' => now(),
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => 'verified',
            'verified_at' => now(),
        ]);
    }
}
