<?php

namespace App\Enums;

enum BrowserName: string
{
    case Chrome = 'chrome';
    case Safari = 'safari';
    case Firefox = 'firefox';
    case Edge = 'edge';
    case Other = 'other';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
