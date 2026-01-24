<?php

namespace App\Enums;

enum OperatingSystem: string
{
    case IOS = 'ios';
    case Android = 'android';
    case Windows = 'windows';
    case MacOS = 'macos';
    case Linux = 'linux';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
