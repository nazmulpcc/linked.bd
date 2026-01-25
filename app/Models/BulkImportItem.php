<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $job_id
 * @property int $row_number
 * @property string $source_url
 * @property string $status
 * @property int|null $link_id
 * @property string|null $error_message
 * @property string|null $qr_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BulkImportItem extends Model
{
    /** @use HasFactory<\Database\Factories\BulkImportItemFactory> */
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public function job(): BelongsTo
    {
        return $this->belongsTo(BulkImportJob::class, 'job_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
        ];
    }
}
