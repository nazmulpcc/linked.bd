<?php

namespace Database\Seeders;

use App\Models\LinkRuleCondition;
use Illuminate\Database\Seeder;

class LinkRuleConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LinkRuleCondition::factory()->count(10)->create();
    }
}
