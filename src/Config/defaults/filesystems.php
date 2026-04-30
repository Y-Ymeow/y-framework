<?php

return [
    'default' => env('FILESYSTEM_DRIVER', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => '__STORAGE_PATH__/app',
            'url' => env('APP_URL') . '/storage',
        ],

        'uploads' => [
            'driver' => 'local',
            'root' => '__STORAGE_PATH__/uploads',
            'url' => env('APP_URL') . '/media',
        ],

        'files' => [
            'driver' => 'local',
            'root' => '__STORAGE_PATH__/files',
            'url' => env('APP_URL') . '/download',
        ],

        'public' => [
            'driver' => 'local',
            'root' => '__STORAGE_PATH__/app/public',
            'url' => env('APP_URL') . '/storage',
        ],
    ],
];