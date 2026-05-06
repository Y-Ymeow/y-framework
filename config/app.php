<?php

return [
    'name' => env('APP_NAME', 'Y-Framework'),
    'env' => env('APP_ENV', 'local'),
    'debug' => env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),
    'locale' => env('APP_LOCALE', 'zh'),
    'key' => env('APP_KEY', 'test-key-for-testing'),
    'cipher' => 'AES-256-CBC',

    'providers' => [
        \Framework\Log\LogServiceProvider::class,
        \Framework\Cache\CacheServiceProvider::class,
        \Framework\Database\DatabaseServiceProvider::class,
        \Framework\Http\Session\SessionServiceProvider::class,
        \Admin\Auth\AuthServiceProvider::class,
        \Framework\Queue\QueueServiceProvider::class,
        \Framework\Scheduler\SchedulerServiceProvider::class,

        \Admin\Services\AdminServiceProvider::class,
    ],

    'debug_providers' => [
        \Framework\DebugBar\DebugBarServiceProvider::class,
    ],

    'modules' => [
    ],

    'aliases' => [],

    'middleware' => [
        'web' => [],
        'api' => [],
        'admin' => [
            \Framework\Http\Middleware\AdminAuthenticate::class,
        ],
    ],
];
