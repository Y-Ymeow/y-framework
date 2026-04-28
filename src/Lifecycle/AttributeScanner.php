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
    }

    private function scanAdminResourceAttributes(ReflectionClass $reflection): void
    {
        $classAttrs = $reflection->getAttributes(\Framework\Admin\Attribute\AdminResource::class);
        if (empty($classAttrs)) {
            return;
        }

        $adminAttr = $classAttrs[0]->newInstance();
        
        // 只注册到 AdminManager，不注册路由
        // 路由由 AdminResourceController 统一处理
        \Framework\Admin\AdminManager::registerResource($reflection->getName());
    }

    private function scanHookAttributes(ReflectionClass $reflection): void
    {
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            foreach ($method->getAttributes(\Framework\Events\Attribute\HookListener::class) as $attr) {
                $listener = $attr->newInstance();
                $instance = $this->resolveInstance($reflection);
                if ($instance === null) continue;

                \Framework\Events\Hook::addAction(
                    $listener->hook,
                    [$instance, $method->getName()],
                    $listener->priority,
                    $listener->acceptedArgs
                );
            }

            foreach ($method->getAttributes(\Framework\Events\Attribute\HookFilter::class) as $attr) {
                $filter = $attr->newInstance();
                $instance = $this->resolveInstance($reflection);
                if ($instance === null) continue;

                \Framework\Events\Hook::addFilter(
                    $filter->hook,
                    [$instance, $method->getName()],
                    $filter->priority,
                    $filter->acceptedArgs
                );
            }
        }
    }

    private function scanRouteAttributes(ReflectionClass $reflection): void
    {
        $classAttrs = $reflection->getAttributes(\Framework\Routing\Attribute\Route::class);
        $prefix = '';
        $classMiddleware = [];

        if (!empty($classAttrs)) {
            $routeAttr = $classAttrs[0]->newInstance();
            $prefix = $routeAttr->prefix;
            $classMiddleware = $routeAttr->middleware;
        }

        $httpAttrs = [
            \Framework\Routing\Attribute\Get::class => 'GET',
            \Framework\Routing\Attribute\Post::class => 'POST',
            \Framework\Routing\Attribute\Put::class => 'PUT',
            \Framework\Routing\Attribute\Delete::class => 'DELETE',
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($httpAttrs as $attrClass => $httpMethod) {
                $attrs = $method->getAttributes($attrClass);
                if (empty($attrs)) continue;

                $attr = $attrs[0]->newInstance();
                $path = rtrim($prefix . '/' . ltrim($attr->path, '/'), '/') ?: '/';
                $name = $attr->name ?: ($reflection->getName() . '::' . $method->getName());
                $middleware = array_merge($classMiddleware, $attr->middleware);

                $this->manager->registerRoute([
                    'method' => $httpMethod,
                    'path' => $path,
                    'handler' => [$reflection->getName(), $method->getName()],
                    'name' => $name,
                    'middleware' => $middleware,
                ]);
            }
        }
    }

    private function scanComponentAttributes(ReflectionClass $reflection): void
    {
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            foreach ($method->getAttributes(\Framework\Component\Attribute\LiveListener::class) as $attr) {
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
                $className = $reflection->getName();
                
                if (function_exists('app')) {
                    try {
                        return app($className);
                    } catch (\Throwable) {
                    }
                }
                
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
