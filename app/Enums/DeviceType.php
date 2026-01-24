<?php

namespace App\Enums;

enum DeviceType: string
{
    case Mobile = 'mobile';
    case Desktop = 'desktop';
    case Tablet = 'tablet';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
