<?php

return [
    'driver' => env('SESSION_DRIVER', 'file'),

    'lifetime' => env('SESSION_LIFETIME', 120),

    'path' => '/',

    'domain' => env('SESSION_DOMAIN', null),

    'secure' => env('SESSION_SECURE', false),

    'httponly' => true,

    'same_site' => 'lax',

    'files' => '__STORAGE_PATH__/sessions',

    'cookie' => env('SESSION_COOKIE', 'y_session'),
];
