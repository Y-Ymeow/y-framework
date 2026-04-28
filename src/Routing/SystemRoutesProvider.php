<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\StreamedResponse;

class SystemRoutesProvider
{
    public static function register(Router $router, string $basePath): void
    {
        $route = new SystemRoute($basePath);

        $router->any('/media/{path...}', [$route, 'media']);
        $router->any('/assets/{path...}', [$route, 'assets']);
        $router->get('/download/{path...}', [$route, 'download']);
        $router->get('/stream/{path...}', [$route, 'stream']);
        $router->get('/_css', [$route, 'css']);
        $router->get('/_js', [$route, 'js']);
    }
}
