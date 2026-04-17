<?php

declare(strict_types=1);

use Framework\Cache\FileCache;
use Framework\Config\ConfigRepository;
use Framework\Core\Application;
use Framework\Core\Kernel;
use Framework\Database\DatabaseManager;
use Framework\Routing\RouteCollector;
use Framework\Routing\RouteCompiler;
use Framework\Routing\RouteFileLoader;
use Framework\Routing\Router;
use Framework\Support\ControllerResolver;

require_once __DIR__ . '/../vendor/autoload.php';

$basePath = dirname(__DIR__);
$config = new ConfigRepository([
    ...require $basePath . '/config/app.php',
    'database' => require $basePath . '/config/database.php',
]);
$cache = new FileCache($basePath . '/storage/cache');
$app = new Application($basePath, $config, $cache);
$GLOBALS['app'] = $app;

foreach ((require $basePath . '/routes/actions.php') as $actionFile) {
    require_once $actionFile;
}

$app->bind(DatabaseManager::class, static fn (Application $app): DatabaseManager => new DatabaseManager($app->config()->get('database', [])));
$app->instance(\Framework\Http\Session::class, new \Framework\Http\Session());
$app->bind(\Framework\View\CompilerInterface::class, static fn (Application $app): \Framework\View\CompilerInterface => new \Framework\View\Compiler());
$app->bind(\Framework\View\Engine::class, static function (Application $app): \Framework\View\Engine {
    return new \Framework\View\Engine(
        $app,
        $app->make(\Framework\View\CompilerInterface::class),
        $app->basePath('resources/views'),
        $app->basePath('storage/cache/views'),
        (bool) $app->config()->get('app.debug', true)
    );
});
$app->bind(ControllerResolver::class, static fn (Application $app): ControllerResolver => new ControllerResolver($app));
$app->bind(Router::class, static function (Application $app) use ($basePath, $cache): Router {
    $routeCacheFile = 'routes.php';
    $compiledPath = $cache->path($routeCacheFile);

    if ((bool) $app->config()->get('app.route_cache', true) && is_file($compiledPath)) {
        /** @var list<array{path: string, methods: list<string>, handler: mixed, name: ?string, pattern: string, parameterNames: list<string>}> $payload */
        $payload = require $compiledPath;

        return Router::fromCompiled($payload);
    }

    $collector = new RouteCollector();
    $fileLoader = new RouteFileLoader();
    
    $actionFiles = require $basePath . '/routes/actions.php';
    $attributeRoutes = $collector->collect(
        classes: require $basePath . '/routes/controllers.php',
        files: $actionFiles
    );
    $fileRoutes = $fileLoader->load(require $basePath . '/routes/files.php');
    $routes = [...$attributeRoutes, ...$fileRoutes];
    $compiler = new RouteCompiler();

    if ((bool) $app->config()->get('app.route_cache', true)) {
        $cache->writePhp($routeCacheFile, $compiler->compile($routes));
    }

    return new Router($routes);
});
$app->bind(Kernel::class, static function (Application $app): Kernel {
    $kernel = new Kernel($app, $app->make(Router::class));
    $kernel->pushMiddleware(\Framework\Http\Middleware\VerifyCsrfToken::class);
    return $kernel;
});

return $app;
