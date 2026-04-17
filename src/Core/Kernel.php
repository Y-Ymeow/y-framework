<?php

declare(strict_types=1);

namespace Framework\Core;

use Framework\Http\NotFoundHttpException;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Routing\Router;
use Framework\Support\ControllerResolver;
use Throwable;

final class Kernel
{
    /** @var list<string|MiddlewareInterface> */
    private array $middlewares = [];

    public function __construct(
        private readonly Application $app,
        private readonly Router $router,
    ) {
    }

    /**
     * 注册全局中间件
     */
    public function prependMiddleware(string|MiddlewareInterface $middleware): self
    {
        array_unshift($this->middlewares, $middleware);
        return $this;
    }

    public function pushMiddleware(string|MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function handle(Request $request): Response
    {
        try {
            $matched = $this->router->match($request);
            $request->setAttribute('route', $matched->route);
            $request->setAttribute('routeParameters', $matched->parameters);

            $pipeline = new Pipeline($this->app);

            // 1. 全局中间件
            foreach ($this->middlewares as $middleware) {
                $pipeline->pipe($middleware);
            }

            // 2. 路由中间件
            foreach ($matched->route->middlewares as $middleware) {
                $pipeline->pipe($middleware);
            }

            // 3. 终点处理器 (控制器)
            return $pipeline->process($request, new class ($this->app, $matched->route) implements RequestHandlerInterface {
                public function __construct(
                    private readonly Application $app,
                    private readonly \Framework\Routing\RouteDefinition $route,
                ) {
                }

                public function handle(Request $request): Response
                {
                    $handler = $this->app->make(ControllerResolver::class)->resolve($this->route->handler);
                    $result = $handler($request, $this->app);

                    if ($result instanceof Response) {
                        return $result;
                    }

                    if (is_array($result)) {
                        return Response::json($result);
                    }

                    return new Response((string) $result);
                }
            });
        } catch (NotFoundHttpException $exception) {
            return new Response('Not Found', 404);
        } catch (Throwable $exception) {
            $debug = (bool) $this->app->config()->get('app.debug', false);
            return \Framework\Debug\ErrorHandler::handle($exception, $debug);
        }
    }
}
