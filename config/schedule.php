<?php

declare(strict_types=1);

return [
    'mode' => env('SCHEDULE_MODE', 'route'),

    'route' => [
        'path' => '/_schedule',
        'token' => env('SCHEDULE_TOKEN', 'change-me'),
    ],

    'timezone' => env('SCHEDULE_TIMEZONE', 'UTC'),

    'email' => [
        'enabled' => env('SCHEDULE_EMAIL_ENABLED', false),
        'to' => env('SCHEDULE_EMAIL_TO', ''),
    ],
];
