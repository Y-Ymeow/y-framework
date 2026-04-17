<?php

declare(strict_types=1);

use Framework\Core\Application;

if (! function_exists('app')) {
    function app(?string $id = null): mixed
    {
        /** @var Application $app */
        $app = $GLOBALS['app'];

        if ($id === null) {
            return $app;
        }

        return $app->make($id);
    }
}

if (! function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return app()->config()->get($key, $default);
    }
}

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return app()->basePath($path);
    }
}

if (! function_exists('view')) {
    function view(string $name, array $data = []): string
    {
        return app(\Framework\View\Engine::class)->render($name, $data);
    }
}
