<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $link_id
 * @property int $priority
 * @property string $destination_url
 * @property bool $is_fallback
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LinkRule extends Model
{
    /** @use HasFactory<\Database\Factories\LinkRuleFactory> */
    use HasFactory;

    protected $touches = ['link'];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(LinkRuleCondition::class);
    }

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_fallback' => 'boolean',
            'enabled' => 'boolean',
        ];
    }
}
