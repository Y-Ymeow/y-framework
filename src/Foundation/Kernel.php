<?php

declare(strict_types=1);

namespace Framework\Foundation;

use Framework\Events\Hook;
use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Http\Response\StreamedResponse;
use Framework\Routing\Router;

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
        if (!$this->router->loadCache(paths()->cache('routes.php'))) {
            $scanDirs = config('routes.routes', []);
            $dirs = array_map(fn($dir) => $basePath . '/' . ltrim($dir, '/'), (array)$scanDirs);

            $dirs = array_filter($dirs, fn($dir) => is_dir($dir));

            $frameworkDir = paths()->frameworkSrc();

            $files = [
                $frameworkDir . '/Component/Live/LiveRequestHandler.php',
                $frameworkDir . '/Component/Live/Sse/SseEndpoint.php',
                $frameworkDir . '/Routing/SystemRoute.php',
            ];

            if (!empty($dirs)) {
                $this->router->scan($dirs, $files);
            }

            //SystemRoutesProvider::register($this->router, $basePath);
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
