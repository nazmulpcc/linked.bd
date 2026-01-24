<?php

return [
    'guest_ttl_days' => env('LINK_GUEST_TTL_DAYS', 7),
    'system_domains' => env('SYSTEM_DOMAINS', ''),
    'domain_verification_cname' => env('LINK_DOMAIN_VERIFICATION_CNAME'),
    'dynamic' => [
        'max_rules' => env('LINK_DYNAMIC_MAX_RULES', 10),
        'max_conditions_per_rule' => env('LINK_DYNAMIC_MAX_CONDITIONS_PER_RULE', 8),
        'max_total_conditions' => env('LINK_DYNAMIC_MAX_TOTAL_CONDITIONS', 24),
        'allow_regex' => env('LINK_DYNAMIC_ALLOW_REGEX', false),
        'regex_max_length' => env('LINK_DYNAMIC_REGEX_MAX_LENGTH', 120),
        'cache' => [
            'enabled' => env('LINK_DYNAMIC_CACHE_ENABLED', true),
            'ttl_seconds' => env('LINK_DYNAMIC_CACHE_TTL', 300),
        ],
    ],
];
