<?php

declare(strict_types=1);

namespace Framework\Http;

use Framework\Core\Application;
use RuntimeException;

/**
 * 中间件执行管线
 */
final class Pipeline
{
    /** @var array<int, string|MiddlewareInterface> */
    private array $middlewares = [];

    public function __construct(
        private readonly Application $app
    ) {
    }

    /**
     * 添加中间件
     */
    public function pipe(string|MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * 执行管线
     */
    public function process(Request $request, RequestHandlerInterface $destination): Response
    {
        $handler = $this->createHandler($destination);
        return $handler->handle($request);
    }

    private function createHandler(RequestHandlerInterface $destination): RequestHandlerInterface
    {
        $middlewares = array_reverse($this->middlewares);

        return array_reduce(
            $middlewares,
            function (RequestHandlerInterface $next, string|MiddlewareInterface $middleware) {
                return new class ($this->app, $middleware, $next) implements RequestHandlerInterface {
                    public function __construct(
                        private readonly Application $app,
                        private readonly string|MiddlewareInterface $middleware,
                        private readonly RequestHandlerInterface $next
                    ) {
                    }

                    public function handle(Request $request): Response
                    {
                        $middleware = $this->middleware;
                        if (is_string($middleware)) {
                            $middleware = $this->app->make($middleware);
                        }

                        if (!$middleware instanceof MiddlewareInterface) {
                            throw new RuntimeException(sprintf(
                                'Middleware must implement MiddlewareInterface, %s given.',
                                is_object($middleware) ? get_class($middleware) : gettype($middleware)
                            ));
                        }

                        return $middleware->process($request, $this->next);
                    }
                };
            },
            $destination
        );
    }
}
