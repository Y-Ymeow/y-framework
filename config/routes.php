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
        'path' => base_path('storage/cache/routes'),
    ],
];
