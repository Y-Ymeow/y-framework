<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Component\Live\LiveComponent;
use Framework\Foundation\Application;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Routing\Attribute as Attr;
use Framework\Support\Finder;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Router
{
    private array $routes = [];
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function addRoute(string $method, string $path, mixed $handler, string $name = ''): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'name' => $name ?: ($method . ':' . $path),
            'handler' => $handler,
            'middleware' => [],
        ];
    }

    public function get(string $path, mixed $handler, string $name = ''): void
    {
        $this->addRoute('GET', $path, $handler, $name);
    }

    public function post(string $path, mixed $handler, string $name = ''): void
    {
        $this->addRoute('POST', $path, $handler, $name);
    }

    public function put(string $path, mixed $handler, string $name = ''): void
    {
        $this->addRoute('PUT', $path, $handler, $name);
    }

    public function delete(string $path, mixed $handler, string $name = ''): void
    {
        $this->addRoute('DELETE', $path, $handler, $name);
    }

    public function any(string $path, mixed $handler, string $name = ''): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'] as $method) {
            $this->addRoute($method, $path, $handler, $name);
        }
    }

    public function loadCache(string $cacheFile): bool
    {
        if (file_exists($cacheFile)) {
            $this->routes = require $cacheFile;
            return true;
        }
        return false;
    }

    public function scan(string|array $directories): void
    {
        $finder = new Finder();
        $finder->in($directories)->files()->name('*.php')->recursive(true);
        $files = $finder->getIterator();

        foreach ($files as $file) {
            $className = $this->getClassFromFile($file->getRealPath());
            if ($className === null) continue;

            try {
                $reflection = new \ReflectionClass($className);
            } catch (\Throwable) {
                continue;
            }

            $this->registerClass($reflection);
        }
    }

    public function registerClass(\ReflectionClass $reflection): void
    {
        // 处理 RouteGroup 属性
        $groupAttrs = $reflection->getAttributes(Attr\RouteGroup::class);
        $prefix = '';
        $classMiddleware = [];
        $groupName = '';

        if (!empty($groupAttrs)) {
            $groupAttr = $groupAttrs[0]->newInstance();
            $prefix = $groupAttr->prefix;
            $classMiddleware = $groupAttr->middleware;
            $groupName = $groupAttr->name;
        }

        // 处理 Middleware 属性
        $middlewareAttrs = $reflection->getAttributes(Attr\Middleware::class);
        foreach ($middlewareAttrs as $ma) {
            $m = $ma->newInstance();
            $classMiddleware = array_merge($classMiddleware, (array)$m->middleware);
        }

        // 处理类级别的 Route 属性
        $classRouteAttrs = $reflection->getAttributes(Attr\Route::class);
        foreach ($classRouteAttrs as $ra) {
            $routeAttr = $ra->newInstance();
            $path = rtrim($prefix . '/' . ltrim($routeAttr->path, '/'), '/') ?: '/';
            $methods = (array)$routeAttr->methods;
            $name = $routeAttr->name ?: ($groupName . ($routeAttr->name ? '.' : '') . $reflection->getShortName());
            $middleware = array_merge($classMiddleware, $routeAttr->middleware);

            $handlerMethod = '__invoke';
            if ($reflection->isSubclassOf(\Framework\Component\Live\LiveComponent::class)) {
                $handlerMethod = 'render';
            }

            foreach ($methods as $method) {
                $this->routes[] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                    'name' => $name,
                    'handler' => [$reflection->getName(), $handlerMethod],
                    'middleware' => $middleware,
                ];
            }
        }

        // 处理方法级别的路由属性
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $this->registerMethod($reflection->getName(), $method, $prefix, $classMiddleware, $groupName);
        }
    }

    private function registerMethod(string $className, \ReflectionMethod $method, string $prefix, array $classMiddleware, string $groupName = ''): void
    {
        $attrs = $method->getAttributes(Attr\Route::class);
        foreach ($attrs as $attrRef) {
            $attr = $attrRef->newInstance();
            
            $path = rtrim($prefix . '/' . ltrim($attr->path, '/'), '/') ?: '/';
            $middleware = array_merge($classMiddleware, $attr->middleware);

            $methodMiddlewareAttrs = $method->getAttributes(Attr\Middleware::class);
            foreach ($methodMiddlewareAttrs as $ma) {
                $m = $ma->newInstance();
                $middleware = array_merge($middleware, (array)$m->middleware);
            }

            $methods = (array)$attr->methods;
            $name = $attr->name ?: ($groupName ? $groupName . '.' : '') . $method->getName();

            foreach ($methods as $httpMethod) {
                $this->routes[] = [
                    'method' => strtoupper($httpMethod),
                    'path' => $path,
                    'name' => $name,
                    'handler' => [$className, $method->getName()],
                    'middleware' => $middleware,
                ];
            }
        }
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $params = $this->matchPath($route['path'], $path);
            if ($params === false) continue;

            return $this->invoke($route, $request, $params);
        }

        return Response::html(Element::make('h1')->text('404 Not Found'), 404);
    }

    private function matchPath(string $routePath, string $requestPath): array|false
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));

        $params = [];
        $wildcardIndex = null;
        $wildcardName = null;
        $consumedMultipleParts = false;

        for ($i = 0; $i < count($routeParts); $i++) {
            $routePart = $routeParts[$i];

            if (str_starts_with($routePart, '{') && str_ends_with($routePart, '}')) {
                if (str_contains($routePart, '...')) {
                    $wildcardIndex = $i;
                    $wildcardName = trim($routePart, '{}.');
                    break;
                }

                $inner = trim($routePart, '{}');
                $paramName = $inner;
                $pattern = null;

                if (str_contains($inner, ':')) {
                    [$paramName, $pattern] = explode(':', $inner, 2);
                }

                $isLastRoutePart = $i === count($routeParts) - 1;
                $hasMoreRequestParts = isset($requestParts[$i]);

                if (!$hasMoreRequestParts) {
                    return false;
                }

                if ($isLastRoutePart && count($requestParts) > count($routeParts)) {
                    $remainingParts = array_slice($requestParts, $i);
                    $value = implode('/', $remainingParts);
                    $consumedMultipleParts = true;
                } else {
                    $value = $requestParts[$i];
                }

                if ($pattern !== null) {
                    if (!preg_match('/^' . $pattern . '$/', $value)) {
                        return false;
                    }
                }

                $params[$paramName] = $value;
            } else {
                if (!isset($requestParts[$i]) || $requestParts[$i] !== $routePart) {
                    return false;
                }
            }
        }

        if ($wildcardIndex !== null) {
            $remainingParts = array_slice($requestParts, $wildcardIndex);
            $params[$wildcardName] = implode('/', $remainingParts);
            return $params;
        }

        if (!$consumedMultipleParts && count($routeParts) !== count($requestParts)) {
            return false;
        }

        return $params;
    }

    private function invoke(array $route, Request $request, array $params): Response
    {
        $request->setRoute(
            $route['name'] ?? '',
            is_array($route['handler'])
                ? ((is_object($route['handler'][0]) ? get_class($route['handler'][0]) : $route['handler'][0]) . '::' . $route['handler'][1])
                : 'closure',
            $params
        );

        $handler = $route['handler'];

        if ($handler instanceof \Closure) {
            return $this->invokeCallable($handler, $request, $params);
        }

        if (is_array($handler)) {
            [$classOrInstance, $methodName] = $handler;
            $instance = is_object($classOrInstance) ? $classOrInstance : $this->app->make($classOrInstance);



            $ref = new \ReflectionMethod($instance, $methodName);
            $args = $this->resolveArguments($ref, $request, $params);
            $result = $ref->invoke($instance, ...$args);

            if ($instance instanceof LiveComponent) {
                // 如果结果已经是 Response 或 Document，直接返回
                if ($result instanceof Response) {
                    return $result;
                }

                // 返回实例本身，这样 Document::main() 就能接收并正确处理它
                return $this->normalizeResponse($instance);
            }

            return $this->normalizeResponse($result);
        }

        if (is_callable($handler)) {
            return $this->invokeCallable($handler, $request, $params);
        }

        return Response::html('Invalid route handler', 500);
    }

    private function invokeCallable(callable $handler, Request $request, array $params): Response
    {
        $ref = new \ReflectionFunction($handler);
        $args = $this->resolveArguments($ref, $request, $params);
        $result = $handler(...$args);

        return $this->normalizeResponse($result);
    }

    private function resolveArguments(\ReflectionFunctionAbstract $reflector, Request $request, array $params): array
    {
        $args = [];
        foreach ($reflector->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if ($type && $type instanceof \ReflectionNamedType && $type->getName() === Request::class) {
                $args[] = $request;
            } elseif (isset($params[$name])) {
                $args[] = $params[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($type && $type->allowsNull()) {
                $args[] = null;
            } else {
                $args[] = null;
            }
        }
        return $args;
    }

    private function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        if ($result instanceof LiveComponent || $result instanceof Element || $result instanceof UXComponent) {
            return Response::html($result);
        }

        return Response::html(Element::make('div')->text('No content'), 204);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    private function getClassFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        if (
            preg_match('/namespace\s+([^;]+);/i', $content, $ns) &&
            preg_match('/class\s+(\w+)/i', $content, $class)
        ) {
            return $ns[1] . '\\' . $class[1];
        }

        if (preg_match('/class\s+(\w+)/i', $content, $class)) {
            return $class[1];
        }

        return null;
    }
}
