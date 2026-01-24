<?php

namespace Database\Seeders;

use App\Models\LinkRule;
use Illuminate\Database\Seeder;

class LinkRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LinkRule::factory()->count(5)->create();
    }
}
