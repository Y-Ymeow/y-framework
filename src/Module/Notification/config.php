<?php

return [
    'database' => [
        'enabled' => true,
        'table' => 'notifications',
        'prune_after_days' => 30,
    ],

    'channels' => [
        'database' => true,
        'sse' => true,
    ],

    'sse' => [
        'channel' => 'notifications',
    ],
];
