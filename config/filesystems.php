<?php

return [
    'default' => env('FILESYSTEM_DRIVER', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'url' => env('APP_URL') . '/storage',
        ],

        'uploads' => [
            'driver' => 'local',
            'root' => storage_path('uploads'),
            'url' => env('APP_URL') . '/media',
        ],

        'files' => [
            'driver' => 'local',
            'root' => storage_path('files'),
            'url' => env('APP_URL') . '/download',
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
        ],
    ],
];
