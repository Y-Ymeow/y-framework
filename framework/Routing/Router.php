<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Component\Live\LiveComponent;
use Framework\Foundation\AppEnvironment;
use Framework\Foundation\Application;
use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Http\Response\StreamedResponse;
use Framework\Routing\Attribute as Attr;
use Framework\Support\File;
use Framework\Support\Finder;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Router
{
    private RouteCollection $routes;
    private Application $app;
    private MiddlewareManager $middlewareManager;
    private array $groupStack = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->routes = new RouteCollection();
        $this->middlewareManager = new MiddlewareManager();
    }

    public function addRoute(string $method, string $path, mixed $handler, string $name = ''): Route
    {
        $middleware = $this->resolveGroupMiddleware();
        $prefix = $this->resolveGroupPrefix();
        $groupName = $this->resolveGroupName();

        $fullPath = rtrim($prefix . '/' . ltrim($path, '/'), '/') ?: '/';
        $fullName = $groupName ? ($name ? $groupName . '.' . $name : $groupName) : $name;

        $route = new Route($method, $fullPath, $handler, $fullName, $middleware, $groupName ?: null);
        $this->routes->add($route);
        return $route;
    }

    public function get(string $path, mixed $handler, string $name = ''): Route
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }

    public function post(string $path, mixed $handler, string $name = ''): Route
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }

    public function put(string $path, mixed $handler, string $name = ''): Route
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }

    public function patch(string $path, mixed $handler, string $name = ''): Route
    {
        return $this->addRoute('PATCH', $path, $handler, $name);
    }

    public function delete(string $path, mixed $handler, string $name = ''): Route
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }

    public function options(string $path, mixed $handler, string $name = ''): Route
    {
        return $this->addRoute('OPTIONS', $path, $handler, $name);
    }

    public function any(string $path, mixed $handler, string $name = ''): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'] as $method) {
            $this->addRoute($method, $path, $handler, $name);
        }
    }

    public function match(array $methods, string $path, mixed $handler, string $name = ''): Route
    {
        $first = null;
        foreach ($methods as $method) {
            $route = $this->addRoute($method, $path, $handler, $name);
            if ($first === null) $first = $route;
        }
        return $first;
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;

        $callback($this);

        array_pop($this->groupStack);
    }

    public function loadCache(string $cacheFile): bool
    {
        if (file_exists($cacheFile)) {
            $data = require $cacheFile;
            $this->routes = RouteCollection::fromArray($data);
            return true;
        }
        return false;
    }

    public function scan(string|array $directories, array $extendFiles = []): void
    {
        $finder = new Finder();
        $finder->in($directories)->files()->name('*.php')->recursive(true);
        $files = $finder->getIterator();

        $files = array_merge($files, $extendFiles);

        foreach ($files as $file) {
            if (is_string($file)) {
                $file = new File($file);
            }

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

        $middlewareAttrs = $reflection->getAttributes(Attr\Middleware::class);
        foreach ($middlewareAttrs as $ma) {
            $m = $ma->newInstance();
            $classMiddleware = array_merge($classMiddleware, (array)$m->middleware);
        }

        $classRouteAttrs = $reflection->getAttributes(Attr\Route::class);
        foreach ($classRouteAttrs as $ra) {
            $routeAttr = $ra->newInstance();
            $path = rtrim($prefix . '/' . ltrim($routeAttr->path, '/'), '/') ?: '/';
            $methods = (array)$routeAttr->methods;
            $name = $routeAttr->name ?: ($groupName . ($routeAttr->name ? '.' : '') . $reflection->getShortName());
            $middleware = array_merge($classMiddleware, $routeAttr->middleware);

            $handlerMethod = '__invoke';
            if ($reflection->isSubclassOf(\Framework\Component\Live\LiveComponent::class)) {
                $handlerMethod = 'toHtml';
            }

            foreach ($methods as $method) {
                $this->routes->add(new Route(
                    $method,
                    $path,
                    [$reflection->getName(), $handlerMethod],
                    $name,
                    $middleware,
                    $groupName ?: null,
                ));
            }
        }

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

            $filteredClassMiddleware = [];
            foreach ($classMiddleware as $mw) {
                if (is_string($mw)) {
                    $filteredClassMiddleware[] = $mw;
                }
            }

            $refClass = new \ReflectionClass($className);
            $classMiddlewareAttrs = $refClass->getAttributes(Attr\Middleware::class);
            foreach ($classMiddlewareAttrs as $ma) {
                $m = $ma->newInstance();
                if ($m->appliesTo($method->getName())) {
                    $filteredClassMiddleware = array_merge($filteredClassMiddleware, (array)$m->middleware);
                }
            }

            $middleware = array_merge($filteredClassMiddleware, $attr->middleware);

            $methodMiddlewareAttrs = $method->getAttributes(Attr\Middleware::class);
            foreach ($methodMiddlewareAttrs as $ma) {
                $m = $ma->newInstance();
                $middleware = array_merge($middleware, (array)$m->middleware);
            }

            $methods = (array)$attr->methods;
            $name = $attr->name ?: ($groupName ? $groupName . '.' : '') . $method->getName();

            foreach ($methods as $httpMethod) {
                $this->routes->add(new Route(
                    $httpMethod,
                    $path,
                    [$className, $method->getName()],
                    $name,
                    $middleware,
                    $groupName ?: null,
                ));
            }
        }
    }

    public function dispatch(Request $request): Response|StreamedResponse
    {
        $method = $request->method();
        $path = '/' . trim($request->path(), '/') ?: '/';

        foreach ($this->routes->getByMethod($method) as $route) {
            $params = $route->match($path);
            if ($params !== false) {
                return $this->invokeWithMiddleware($route, $request, $params);
            }
        }

        if ($method === 'OPTIONS') {
            return new Response('', 204, ['Allow' => implode(', ', $this->getAllowedMethods($path))]);
        }

        if ($method !== 'HEAD') {
            $allowed = $this->getAllowedMethods($path);
            if (!empty($allowed)) {
                return new Response(\Framework\Error\ErrorPage::render(405), 405, ['Allow' => implode(', ', $allowed)]);
            }
        }

        return $this->sendContent(Element::make('div')->html(\Framework\Error\ErrorPage::renderElement(404)), 404);
    }

    public function getRouteByName(string $name): ?Route
    {
        return $this->routes->getByName($name);
    }

    public function getRoutes(): array
    {
        return $this->routes->toArray();
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->routes;
    }

    public function getMiddlewareManager(): MiddlewareManager
    {
        return $this->middlewareManager;
    }

    private function invokeWithMiddleware(Route $route, Request $request, array $params): Response|StreamedResponse
    {
        $middleware = $route->getMiddleware();
        $groupName = $route->getGroup();

        $destination = function (Request $req) use ($route, $params): Response|StreamedResponse {
            return $this->invoke($route, $req, $params);
        };

        return $this->middlewareManager->pipe($request, $destination, $middleware, $groupName);
    }

    private function invoke(Route $route, Request $request, array $params): Response|StreamedResponse
    {
        $request->setRoute(
            $route->getName(),
            is_array($route->getHandler())
                ? ((is_object($route->getHandler()[0]) ? get_class($route->getHandler()[0]) : $route->getHandler()[0]) . '::' . $route->getHandler()[1])
                : 'closure',
            $params
        );

        $handler = $route->getHandler();

        if ($handler instanceof \Closure) {
            return $this->invokeCallable($handler, $request, $params);
        }

        if (is_array($handler)) {
            [$classOrInstance, $methodName] = $handler;
            $instance = is_object($classOrInstance) ? $classOrInstance : $this->app->make($classOrInstance);

            if ($instance instanceof LiveComponent && $methodName === '__invoke') {
                $methodName = '_invoke';
            }

            $ref = new \ReflectionMethod($instance, $methodName);
            $args = $this->resolveArguments($ref, $request, $params);
            $result = $ref->invoke($instance, ...$args);

            if ($instance instanceof LiveComponent) {
                $instance->_invoke($params);
                if ($result instanceof Response) {
                    return $result;
                }
                return $this->normalizeResponse($instance);
            }

            return $this->normalizeResponse($result);
        }

        if (is_callable($handler)) {
            return $this->invokeCallable($handler, $request, $params);
        }

        return $this->sendContent(Element::make('h1')->text('Invalid route handler'), 500);
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

    private function getAllowedMethods(string $path): array
    {
        $methods = [];
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'] as $method) {
            foreach ($this->routes->getByMethod($method) as $route) {
                if ($route->match($path) !== false) {
                    $methods[] = $method;
                    break;
                }
            }
        }
        return $methods;
    }

    private function sendContent(mixed $content, int $statusCode): Response
    {
        if (AppEnvironment::isWasm()) {
            return Response::wasm($content, status: $statusCode);
        }
        return Response::html($content, $statusCode);
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
            return $this->sendContent($result, 200);
        }
        if ($result instanceof LiveComponent || $result instanceof Element || $result instanceof UXComponent) {
            if ($result instanceof LiveComponent) {
                $result->_invoke();
            }
            return $this->sendContent($result, 200);
        }
        return $this->sendContent(Element::make('div')->text('No content'), 204);
    }

    private function resolveGroupPrefix(): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return $prefix;
    }

    private function resolveGroupMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array)$group['middleware']);
            }
        }
        return $middleware;
    }

    private function resolveGroupName(): string
    {
        $name = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['name'])) {
                $name .= ($name ? '.' : '') . trim($group['name'], '.');
            }
        }
        return $name;
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
