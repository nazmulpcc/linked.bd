<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $link_id
 * @property string $token
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LinkAccessToken extends Model
{
    /** @use HasFactory<\Database\Factories\LinkAccessTokenFactory> */
    use HasFactory;

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
