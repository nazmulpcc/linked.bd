<?php

namespace Database\Factories;

use App\Models\BulkImportItem;
use App\Models\BulkImportJob;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BulkImportItem>
 */
class BulkImportItemFactory extends Factory
{
    protected $model = BulkImportItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => BulkImportJob::factory(),
            'row_number' => $this->faker->numberBetween(1, 5000),
            'source_url' => $this->faker->url(),
            'status' => BulkImportItem::STATUS_QUEUED,
            'link_id' => null,
            'error_message' => null,
            'qr_status' => null,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => BulkImportItem::STATUS_SUCCEEDED,
            'link_id' => Link::factory(),
        ]);
    }

    public function failed(?string $message = null): static
    {
        return $this->state(fn () => [
            'status' => BulkImportItem::STATUS_FAILED,
            'error_message' => $message ?? 'Invalid URL',
        ]);
    }
}
