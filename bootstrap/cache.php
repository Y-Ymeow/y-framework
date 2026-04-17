<?php

declare(strict_types=1);

use Framework\Core\Application;
use Framework\Routing\RouteCollector;
use Framework\Routing\RouteCompiler;
use Framework\Routing\RouteFileLoader;

/** @var Application $app */
$app = require __DIR__ . '/app.php';
$collector = new RouteCollector();
$fileLoader = new RouteFileLoader();
$routes = [
    ...$collector->collect(require $app->basePath('routes/controllers.php')),
    ...$fileLoader->load(require $app->basePath('routes/files.php')),
];
$app->cache()->writePhp('routes.php', (new RouteCompiler())->compile($routes));

echo "Route cache built.\n";
