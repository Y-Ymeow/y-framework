<?php

declare(strict_types=1);

namespace Framework\Foundation;

use Framework\Events\Hook;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\StreamedResponse;
use Framework\Routing\Router;
use Framework\Routing\SystemRoutesProvider;

class Kernel
{
    private Application $app;
    private Router $router;
    private bool $bootstrapped = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->router = $app->make(Router::class);
        $app->instance(Router::class, $this->router);
    }

    public function bootstrap(): void
    {
        if ($this->bootstrapped) return;

        $this->app->bootstrapProviders();

        Hook::fire('app.booting');

        $basePath = $this->app->basePath();

        // 尝试从缓存加载路由
        if (!$this->router->loadCache($basePath . '/storage/cache/routes.php')) {
            $scanDirs = config('routes.routes', config('app.scan_dirs', []));
            $dirs = array_map(fn($dir) => $basePath . '/' . ltrim($dir, '/'), (array)$scanDirs);

            $componentDir = $basePath . '/src/Component';
            if (is_dir($componentDir)) {
                $dirs[] = $componentDir;
            }

            $dirs = array_filter($dirs, fn($dir) => is_dir($dir));

            if (!empty($dirs)) {
                $this->router->scan($dirs);
            }

            SystemRoutesProvider::register($this->router, $basePath);
        }

        // 加载 LiveComponent Action 缓存
        $liveCacheFile = $basePath . '/storage/cache/live_components.php';
        if (file_exists($liveCacheFile)) {
            \Framework\Component\LiveComponent::setGlobalActionCache(require $liveCacheFile);
        }

        Hook::fire('app.booted');

        $this->bootstrapped = true;
    }

    public function handle(Request $request): Response|StreamedResponse
    {
        $this->bootstrap();
        $this->app->instance(Request::class, $request);

        Hook::fire('request.received', $request);

        $response = $this->router->dispatch($request);

        Hook::fire('response.created', $response, $request);

        $response = Hook::filter('response.sending', $response, $request);

        return $response;
    }

    public function terminate(Request $request, Response|StreamedResponse $response): void
    {
        Hook::fire('response.sent', $response, $request);
    }

    public function getRouter(): Router
    {
        return $this->router;
    }
}
