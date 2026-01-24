<?php

namespace App\Enums;

enum ConditionType: string
{
    case Country = 'country';
    case DeviceType = 'device_type';
    case OperatingSystem = 'operating_system';
    case Browser = 'browser';
    case ReferrerDomain = 'referrer_domain';
    case ReferrerPath = 'referrer_path';
    case UtmSource = 'utm_source';
    case UtmMedium = 'utm_medium';
    case UtmCampaign = 'utm_campaign';
    case Language = 'language';
    case TimeWindow = 'time_window';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
