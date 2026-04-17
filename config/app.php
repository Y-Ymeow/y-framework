<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Y Framework',
        'env' => $_ENV['APP_ENV'] ?? 'dev',
        'debug' => ($_ENV['APP_DEBUG'] ?? '1') === '1',
        'route_cache' => true,
    ],
];
