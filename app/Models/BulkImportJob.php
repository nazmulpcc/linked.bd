<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $domain_id
 * @property string $status
 * @property int $total_count
 * @property int $processed_count
 * @property int $success_count
 * @property int $failed_count
 * @property string|null $default_password_hash
 * @property \Illuminate\Support\Carbon|null $default_expires_at
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BulkImportJob extends Model
{
    /** @use HasFactory<\Database\Factories\BulkImportJobFactory> */
    use HasFactory;

    use HasUlids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BulkImportItem::class, 'job_id');
    }

    protected function casts(): array
    {
        return [
            'total_count' => 'integer',
            'processed_count' => 'integer',
            'success_count' => 'integer',
            'failed_count' => 'integer',
            'default_expires_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
