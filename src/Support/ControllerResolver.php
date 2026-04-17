<?php

declare(strict_types=1);

namespace Framework\Support;

use Framework\Core\Application;
use RuntimeException;

/**
 * 灵活的处理器解析器：支持 Class@method, Closure, 和全局函数
 */
final class ControllerResolver
{
    public function __construct(
        private readonly Application $app
    ) {
    }

    public function resolve(mixed $handler): callable
    {
        // 1. 如果本身就是可调用的 (Closure 或已实例化的类)
        if (is_callable($handler)) {
            return $handler;
        }

        // 2. 处理 [Class, Method] 数组
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = $this->app->make($class);
            return [$instance, $method];
        }

        // 3. 处理 "Class@method" 字符串
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler);
            $instance = $this->app->make($class);
            return [$instance, $method];
        }

        // 4. 处理 "ActionFunctionName" 字符串 (全局函数)
        if (is_string($handler) && function_exists($handler)) {
            return $handler;
        }

        // 5. 尝试作为单操作类 (__invoke)
        if (is_string($handler) && class_exists($handler)) {
            $instance = $this->app->make($handler);
            if (is_callable($instance)) {
                return $instance;
            }
        }

        throw new RuntimeException("Could not resolve handler: " . print_r($handler, true));
    }
}
