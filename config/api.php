<?php

declare(strict_types=1);

return [
    'prefix' => 'api',
    'middleware' => ['api'],
    'resources' => [
        'posts' => \App\Api\PostController::class,
        'categories' => \App\Api\CategoryController::class,
        'tags' => \App\Api\TagController::class,
        'media' => \App\Api\MediaController::class,
    ],
];