<?php

namespace Database\Factories;

use App\Models\BulkImportJob;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BulkImportJob>
 */
class BulkImportJobFactory extends Factory
{
    protected $model = BulkImportJob::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'domain_id' => Domain::factory()->platform(),
            'status' => BulkImportJob::STATUS_PENDING,
            'total_count' => 0,
            'processed_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'default_password_hash' => null,
            'default_expires_at' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
