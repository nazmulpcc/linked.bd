<?php

namespace Database\Factories;

use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LinkVisit>
 */
class LinkVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_id' => Link::factory(),
            'link_rule_id' => null,
            'visited_at' => now(),
            'referrer_host' => fake()->optional()->domainName(),
            'device_type' => fake()->randomElement(['mobile', 'desktop']),
            'browser' => fake()->randomElement(['chrome', 'safari', 'firefox', 'edge']),
            'country_code' => fake()->optional()->countryCode(),
            'resolved_destination_url' => fake()->optional()->url(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
