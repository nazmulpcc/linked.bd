<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $hostname
 * @property string $type
 * @property string $status
 * @property string|null $verification_method
 * @property string|null $verification_token
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Domain extends Model
{
    /** @use HasFactory<\Database\Factories\DomainFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }
}
