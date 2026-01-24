<?php

namespace Database\Factories;

use App\Enums\ConditionOperator;
use App\Enums\ConditionType;
use App\Models\LinkRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LinkRuleCondition>
 */
class LinkRuleConditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'link_rule_id' => LinkRule::factory(),
            'condition_type' => ConditionType::Country,
            'operator' => ConditionOperator::Equals,
            'value' => ['US'],
        ];
    }
}
