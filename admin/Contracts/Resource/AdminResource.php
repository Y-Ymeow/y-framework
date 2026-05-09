<?php

declare(strict_types=1);

namespace Admin\Contracts\Resource;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AdminResource
{
    public function __construct(
        public string $name = '',
        public string $model = '',
        public string $title = '',
        public string $icon = '',
        public ?string $routePrefix = null,
        public array $middleware = [],
        public string $group = '',
        public int $sort = 50,
        public string $formWidth = '',
    ) {}
}