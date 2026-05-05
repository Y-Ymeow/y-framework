<?php

return [
    'routes' => [
        'app/',
        'admin/',
        'framework/Ux/',
        'framework/View/',
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