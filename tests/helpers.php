<?php

declare(strict_types=1);

if (!function_exists('test_env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('test_config')) {
    function config($key, $default = null) {
        static $config = [];
        if (is_array($key)) { $config = array_merge($config, $key); return; }
        $keys = explode('.', $key);
        $value = $config;
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) { return $default; }
            $value = $value[$k];
        }
        return $value;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(?string $path = null): string {
        return $path ? 'http://localhost' . '/' . ltrim($path, '/') : 'http://localhost';
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string {
        return '/route/' . $name . '?' . http_build_query($params);
    }
}

if (!function_exists('cache')) {
    function cache() {
        return \Tests\Support\TestCache::getInstance();
    }
}
