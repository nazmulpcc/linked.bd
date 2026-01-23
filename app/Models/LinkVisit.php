<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $link_id
 * @property string|null $referrer_host
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $country_code
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $visited_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LinkVisit extends Model
{
    /** @use HasFactory<\Database\Factories\LinkVisitFactory> */
    use HasFactory;

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }
}
