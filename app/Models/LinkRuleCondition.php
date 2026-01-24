<?php

namespace App\Models;

use App\Enums\ConditionOperator;
use App\Enums\ConditionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $link_rule_id
 * @property \App\Enums\ConditionType $condition_type
 * @property \App\Enums\ConditionOperator $operator
 * @property array<int|string, mixed>|string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LinkRuleCondition extends Model
{
    /** @use HasFactory<\Database\Factories\LinkRuleConditionFactory> */
    use HasFactory;

    public function rule(): BelongsTo
    {
        return $this->belongsTo(LinkRule::class, 'link_rule_id');
    }

    protected function casts(): array
    {
        return [
            'condition_type' => ConditionType::class,
            'operator' => ConditionOperator::class,
            'value' => 'array',
        ];
    }
}
