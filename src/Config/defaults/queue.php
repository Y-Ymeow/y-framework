<?php

declare(strict_types=1);

return [
    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
        ],

        'redis' => [
            'driver' => 'redis',
            'dsn' => env('REDIS_URL', 'redis://localhost:6379'),
            'prefix' => env('QUEUE_PREFIX', 'queue:'),
        ],
    ],

    'mode' => env('QUEUE_MODE', 'route'),

    'route' => [
        'path' => '/_queue',
        'token' => env('QUEUE_TOKEN', 'change-me'),
    ],
];