<?php

namespace App\Models;

use App\Enums\LinkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $ulid
 * @property int $domain_id
 * @property int|null $user_id
 * @property string|null $code
 * @property string|null $alias
 * @property \App\Enums\LinkType $link_type
 * @property string $destination_url
 * @property string|null $fallback_destination_url
 * @property string|null $password_hash
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int $click_count
 * @property \Illuminate\Support\Carbon|null $last_accessed_at
 * @property string|null $qr_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Link extends Model
{
    /** @use HasFactory<\Database\Factories\LinkFactory> */
    use HasFactory;

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(LinkAccessToken::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(LinkVisit::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(LinkRule::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $link) {
            if (! $link->ulid) {
                $link->ulid = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected function casts(): array
    {
        return [
            'link_type' => LinkType::class,
            'expires_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'click_count' => 'integer',
        ];
    }
}
