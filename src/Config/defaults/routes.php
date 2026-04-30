<?php

return [
    'routes' => [
        'src/Admin',
        'admin/Pages',
        'admin/Resources',
        'app/Controllers',
        'app/Pages',
    ],

    'middleware' => [
        'web' => [],
        'api' => [],
        'admin' => [],
    ],

    'cache' => [
        'enabled' => env('ROUTE_CACHE', false),
        'path' => '__BASE_PATH__/storage/cache/routes',
    ],
];