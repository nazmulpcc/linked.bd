<?php

return [
    'abilities' => [
        'links' => [
            'read' => 'links:read',
            'write' => 'links:write',
        ],
        'domains' => [
            'read' => 'domains:read',
            'write' => 'domains:write',
        ],
        'bulk' => [
            'read' => 'bulk:read',
            'write' => 'bulk:write',
        ],
    ],
    'rate_limits' => [
        'api_per_token' => 120,
        'bulk_per_token' => 5,
    ],
];
