<?php

declare(strict_types=1);

return [
    'default' => 'default',
    'connections' => [
        'default' => [
            'dsn' => $_ENV['DB_DSN'] ?? 'sqlite:' . dirname(__DIR__) . '/storage/database.sqlite',
            'username' => $_ENV['DB_USERNAME'] ?? null,
            'password' => $_ENV['DB_PASSWORD'] ?? null,
            'options' => [],
        ],
    ],
];
