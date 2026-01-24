<?php

namespace App\Enums;

enum LinkType: string
{
    case Static = 'static';
    case Dynamic = 'dynamic';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
