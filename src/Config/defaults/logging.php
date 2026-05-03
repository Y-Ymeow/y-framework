<?php

return [
    'default' => env('LOG_CHANNEL', 'daily'),

    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => '__STORAGE_PATH__/logs/app.log',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => '__STORAGE_PATH__/logs/app.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAYS', 14),
        ],

        'stderr' => [
            'driver' => 'stderr',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],
    ],
];
