<?php

declare(strict_types=1);

namespace Framework\Lifecycle;

use Framework\Support\Finder;
use ReflectionClass;
use ReflectionMethod;

class AttributeScanner
{
    private LifecycleManager $manager;

    public function __construct(LifecycleManager $manager)
    {
        $this->manager = $manager;
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
                if (!class_exists($className) && !interface_exists($className) && !trait_exists($className)) {
                    require_once $file->getRealPath();
                }
                $reflection = new ReflectionClass($className);
            } catch (\Throwable) {
                continue;
            }

            $this->scanClass($reflection);
        }
    }

    private function scanClass(ReflectionClass $reflection): void
    {
        $this->scanHookAttributes($reflection);
        $this->scanRouteAttributes($reflection);
        $this->scanComponentAttributes($reflection);
        $this->scanAdminResourceAttributes($reflection);
        $this->scanAdminPageAttributes($reflection);
        $this->scanScheduleAttributes($reflection);
    }

    private function scanAdminPageAttributes(ReflectionClass $reflection): void
    {
        if ($reflection->isSubclassOf(\Framework\Admin\Page\PageInterface::class) && !$reflection->isAbstract()) {
            \Framework\Admin\AdminManager::registerPage($reflection->getName());
        }
    }

    private function scanScheduleAttributes(ReflectionClass $reflection): void
    {
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            $attrs = $method->getAttributes(\Framework\Scheduler\Attribute\Schedule::class);
            foreach ($attrs as $attr) {
                $scheduleAttr = $attr->newInstance();
                
                // 注册到 Scheduler
                $this->manager->registerSchedule([
                    'class' => $reflection->getName(),
                    'method' => $method->getName(),
                    'expression' => $scheduleAttr->expression,
                ]);
            }
        }
    }

    private function scanAdminResourceAttributes(ReflectionClass $reflection): void
    {
        $classAttrs = $reflection->getAttributes(\Framework\Admin\Attribute\AdminResource::class);
        if (empty($classAttrs)) {
            return;
        }

        $adminAttr = $classAttrs[0]->newInstance();
        
        // 注册到 AdminManager
        // 路由由 AdminManager::registerRoutes 统一注册
        \Framework\Admin\AdminManager::registerResource($reflection->getName());
    }

    private function scanHookAttributes(ReflectionClass $reflection): void
    {
        $attributeClasses = [
            \Framework\Events\Attribute\Listen::class,
            \Framework\Events\Attribute\HookListener::class,
            \Framework\Events\Attribute\HookFilter::class,
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            foreach ($attributeClasses as $attrClass) {
                foreach ($method->getAttributes($attrClass) as $attr) {
                    $instance = $attr->newInstance();
                    $resolved = $this->resolveInstance($reflection);
                    if ($resolved === null) continue;

                    $methodName = $method->getName();
                    $callback = function (...$args) use ($resolved, $methodName) {
                        return $resolved->{$methodName}(...$args);
                    };

                    $eventName = $instance->event ?? $instance->hook ?? '';
                    $priority = $instance->priority ?? 0;

                    \Framework\Events\Hook::getInstance()->on($eventName, $callback, $priority);
                }
            }
        }
    }

    private function scanRouteAttributes(ReflectionClass $reflection): void
    {
        // 处理 RouteGroup 属性
        $groupAttrs = $reflection->getAttributes(\Framework\Routing\Attribute\RouteGroup::class);
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
        $middlewareAttrs = $reflection->getAttributes(\Framework\Routing\Attribute\Middleware::class);
        foreach ($middlewareAttrs as $ma) {
            $m = $ma->newInstance();
            $classMiddleware = array_merge($classMiddleware, (array)$m->middleware);
        }

        // 处理类级别的 Route 属性
        $classRouteAttrs = $reflection->getAttributes(\Framework\Routing\Attribute\Route::class);
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
                $this->manager->registerRoute([
                    'method' => strtoupper($method),
                    'path' => $path,
                    'handler' => [$reflection->getName(), $handlerMethod],
                    'name' => $name,
                    'middleware' => $middleware,
                ]);
            }
        }

        // 处理方法级别的路由属性
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(\Framework\Routing\Attribute\Route::class);
            foreach ($attrs as $attrRef) {
                $attr = $attrRef->newInstance();
                
                $path = rtrim($prefix . '/' . ltrim($attr->path, '/'), '/') ?: '/';
                $middleware = array_merge($classMiddleware, $attr->middleware);

                $methodMiddlewareAttrs = $method->getAttributes(\Framework\Routing\Attribute\Middleware::class);
                foreach ($methodMiddlewareAttrs as $ma) {
                    $m = $ma->newInstance();
                    $middleware = array_merge($middleware, (array)$m->middleware);
                }

                $methods = (array)$attr->methods;
                $name = $attr->name ?: ($groupName ? $groupName . '.' : '') . $method->getName();

                foreach ($methods as $httpMethod) {
                    $this->manager->registerRoute([
                        'method' => strtoupper($httpMethod),
                        'path' => $path,
                        'handler' => [$reflection->getName(), $method->getName()],
                        'name' => $name,
                        'middleware' => $middleware,
                    ]);
                }
            }
        }
    }

    private function scanComponentAttributes(ReflectionClass $reflection): void
    {
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            foreach ($method->getAttributes(\Framework\Component\Live\Attribute\LiveListener::class) as $attr) {
                $listener = $attr->newInstance();
                
                $this->manager->registerComponent([
                    'class' => $reflection->getName(),
                    'method' => $method->getName(),
                    'name' => $reflection->getShortName(),
                    'event' => $listener->event,
                    'priority' => $listener->priority,
                ]);
            }
        }
    }

    private function resolveInstance(ReflectionClass $reflection): ?object
    {
        try {
            if ($reflection->isInstantiable()) {
                return $reflection->newInstance();
            }
        } catch (\Throwable) {
            return null;
        }
        return null;
    }

    private function getClassFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        if (preg_match('/namespace\s+([^;]+);/i', $content, $ns) &&
            preg_match('/class\s+(\w+)/i', $content, $class)) {
            return $ns[1] . '\\' . $class[1];
        }

        if (preg_match('/class\s+(\w+)/i', $content, $class)) {
            return $class[1];
        }

        return null;
    }
}
