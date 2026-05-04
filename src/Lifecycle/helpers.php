<?php

declare(strict_types=1);

if (!function_exists('hook')) {
    function hook(string $name, callable $callback, int $priority = 0): void
    {
        \Framework\Events\Hook::getInstance()->on($name, $callback, $priority);
    }
}

if (!function_exists('fire')) {
    function fire(string $name, mixed ...$args): void
    {
        \Framework\Events\Hook::getInstance()->emit($name, $args);
    }
}

if (!function_exists('apply_filter')) {
    function apply_filter(string $name, mixed $value, mixed ...$args): mixed
    {
        return \Framework\Events\Hook::getInstance()->filter($name, $value, $args);
    }
}

if (!function_exists('register_route')) {
    function register_route(array $route): void
    {
        \Framework\Lifecycle\LifecycleManager::getInstance()->registerRoute($route);
    }
}

if (!function_exists('register_component')) {
    function register_component(array $component): void
    {
        \Framework\Lifecycle\LifecycleManager::getInstance()->registerComponent($component);
    }
}

if (!function_exists('register_service')) {
    function register_service(string $name, string $class, bool $singleton = false, ?string $alias = null): void
    {
        \Framework\Lifecycle\LifecycleManager::getInstance()->registerService($name, $class, $singleton, $alias);
    }
}
