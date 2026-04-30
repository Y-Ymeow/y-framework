<?php

return [
    'default' => env('LOG_CHANNEL', 'single'),

    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => '__BASE_PATH__/storage/logs/app.log',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => '__BASE_PATH__/storage/logs/app.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'stderr' => [
            'driver' => 'stderr',
            'level' => env('LOG_LEVEL', 'debug'),
        ],
    ],
];