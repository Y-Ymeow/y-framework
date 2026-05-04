<?php

declare(strict_types=1);

use Framework\Intl\Translator;

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default instanceof \Closure ? $default() : $default;
    }

    switch (strtolower((string)$value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return null;
    }

    if (str_starts_with((string)$value, '"') && str_ends_with((string)$value, '"')) {
        return substr((string)$value, 1, -1);
    }

    return $value;
}

function base_path(string $path = ''): string
{
    $app = \Framework\Foundation\Application::getInstance();
    if ($app !== null) {
        return $app->basePath($path);
    }
    $base = dirname(__DIR__, 2);
    return $path ? $base . '/' . ltrim($path, '/') : $base;
}

function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path ? '/' . ltrim($path, '/') : ''));
}

function logger(string $message, array $context = []): void
{
    $logManager = app()->make(\Psr\Log\LoggerInterface::class);
    $logManager->info($message, $context);
}

function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = \Framework\Config\ConfigManager::load();
    }

    $keys = explode('.', $key);
    $current = $config;

    foreach ($keys as $segment) {
        if (is_array($current) && array_key_exists($segment, $current)) {
            $current = $current[$segment];
        } else {
            return $default instanceof \Closure ? $default() : $default;
        }
    }

    return $current;
}

function asset(string $path): string
{
    $baseUrl = config('app.url', '');
    return rtrim($baseUrl, '/') . '/assets/' . ltrim($path, '/');
}

function media_url(string $path): string
{
    $baseUrl = config('app.url', '');
    return rtrim($baseUrl, '/') . '/media/' . ltrim($path, '/');
}

function download_url(string $path): string
{
    $baseUrl = config('app.url', '');
    return rtrim($baseUrl, '/') . '/download/' . ltrim($path, '/');
}

function stream_url(string $path): string
{
    $baseUrl = config('app.url', '');
    return rtrim($baseUrl, '/') . '/stream/' . ltrim($path, '/');
}

function public_path(string $path = ''): string
{
    return base_path('public' . ($path ? '/' . ltrim($path, '/') : ''));
}

function vite(string $entry): string
{
    return \Framework\Support\Asset::vite($entry);
}

function vite_css(string $entry): array
{
    return \Framework\Support\Asset::viteCss($entry);
}

function dist(string $entry): string
{
    return \Framework\Support\Asset::dist($entry);
}

function dist_css(string $entry): array
{
    return \Framework\Support\Asset::distCss($entry);
}

function redirect(string $url, int $status = 302): \Framework\Http\Response
{
    return \Framework\Http\Response::redirect($url, $status);
}

function route(string $name, array $parameters = [], bool $absolute = false): string
{
    $app = \Framework\Foundation\Application::getInstance();
    $router = $app->make(\Framework\Routing\Router::class);

    foreach ($router->getRoutes() as $route) {
        if (($route['name'] ?? '') === $name) {
            $path = $route['path'];
            foreach ($parameters as $key => $value) {
                $path = str_replace('{' . $key . '}', (string) $value, $path);
                $path = str_replace('{' . $key . ':...}', (string) $value, $path);
            }
            $path = preg_replace('/\{[^}]+\}/', '', $path);
            if ($absolute) {
                $appUrl = config('app.url', '');
                return rtrim($appUrl, '/') . '/' . ltrim($path, '/');
            }
            return '/' . ltrim($path, '/');
        }
    }

    throw new \RuntimeException("Route [{$name}] not found");
}

function session(): \Framework\Http\Session
{
    return app()->make(\Framework\Http\Session::class);
}

function back(): \Framework\Http\Response
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    return redirect($referer);
}

function abort(int $code, string $message = ''): never
{
    throw new \Framework\Http\HttpException($code, $message);
}

function now(): \DateTimeImmutable
{
    return new \DateTimeImmutable();
}

function class_basename(string|object $class): string
{
    $class = is_object($class) ? get_class($class) : $class;
    return basename(str_replace('\\', '/', $class));
}

function class_uses_recursive(string|object $class): array
{
    if (is_object($class)) {
        $class = get_class($class);
    }

    $results = [];

    foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
        $results += trait_uses_recursive($class);
    }

    return array_unique($results);
}

function trait_uses_recursive(string $trait): array
{
    $traits = class_uses($trait) ?: [];

    foreach ($traits as $trait) {
        $traits += trait_uses_recursive($trait);
    }

    return $traits;
}

function today(): \DateTimeImmutable
{
    return new \DateTimeImmutable('today');
}

function str_contains_all(string $haystack, string ...$needles): bool
{
    foreach ($needles as $needle) {
        if (!str_contains($haystack, $needle)) return false;
    }
    return true;
}

function str_contains_any(string $haystack, string ...$needles): bool
{
    foreach ($needles as $needle) {
        if (!str_contains($haystack, $needle)) return true;
    }
    return false;
}

function auth(): \Framework\Auth\AuthManager
{
    static $instance = null;
    if ($instance === null) {
        $app = \Framework\Foundation\Application::getInstance() ?? app();
        $instance = $app->make(\Framework\Auth\AuthManager::class);
    }
    return $instance;
}

function app(): \Framework\Foundation\Application
{
    return $GLOBALS['app'] ?? throw new \RuntimeException('Application not initialized');
}

/**
 * 获取数据库连接实例
 *
 * @param string|null $connection 连接名称
 * @return \Framework\Database\Connection
 *
 * @example db() 默认连接
 * @example db('tenant') 指定连接
 */
function db(?string $connection = null): \Framework\Database\Connection
{
    return \Framework\Database\Connection::get($connection);
}

/**
 * 获取缓存实例
 *
 * @param string|null $store 缓存存储名（默认使用配置中的默认存储）
 * @return \Psr\SimpleCache\CacheInterface
 *
 * @example cache()->set('key', 'value', 3600)
 * @example cache()->get('key', 'default')
 * @example cache()->remember('key', fn() => expensiveOperation(), 3600)
 */
function cache(?string $store = null): \Psr\SimpleCache\CacheInterface
{
    return app()->make(\Framework\Cache\CacheManager::class)->store($store);
}

function user(): ?\Framework\Auth\Authenticatable
{
    return auth()->user();
}

function gate(): \Framework\Auth\Gate
{
    return \Framework\Auth\Gate::getInstance();
}


if (!function_exists('debug')) {
    function debug(mixed ...$data): void
    {
        \Framework\DebugBar\DebugBar::debug(...$data);
    }
}

if (!function_exists('dump')) {
    function dump(mixed ...$data): void
    {
        \Framework\Support\VarDumper::dump(...$data);
    }
}

if (!function_exists('info')) {
    function info(string $message): void
    {
        \Framework\DebugBar\DebugBar::info($message);
    }
}

if (!function_exists('warn')) {
    function warn(string $message): void
    {
        \Framework\DebugBar\DebugBar::warning($message);
    }
}

if (!function_exists('error')) {
    function error(string $message): void
    {
        \Framework\DebugBar\DebugBar::error($message);
    }
}

if (!function_exists('success')) {
    function success(string $message): void
    {
        \Framework\DebugBar\DebugBar::success($message);
    }
}

if (!function_exists('t')) {
    function t(string $key, array $replace = [], ?string $locale = null): string
    {
        return Translator::get($key, $replace, $locale);
    }
}

if (!function_exists('choice')) {
    function choice(string $key, int|float|array $number, array $replace = [], ?string $locale = null): string
    {
        return Translator::choice($key, $number, $replace, $locale);
    }
}

if (!function_exists('locale')) {
    function locale(?string $newLocale = null): string
    {
        if ($newLocale !== null) {
            Translator::setLocale($newLocale);
        }
        return Translator::getLocale();
    }
}


if (!function_exists('recordUrl')) {
    function recordUrl(string $resource, int $id): string
    {
        return \Framework\Admin\AdminResourceController::recordUrl($resource, $id);
    }
}

if (!function_exists('recordDeleteUrl')) {
    function recordDeleteUrl(string $resource, mixed $id): string
    {
        return \Framework\Admin\AdminResourceController::deleteUrl($resource, $id);
    }
}

if (!function_exists('recordEditUrl')) {
    function recordEditUrl(string $resource, mixed $id): string
    {
        return \Framework\Admin\AdminResourceController::editUrl($resource, $id);
    }
}

if (!function_exists('recordCreateUrl')) {
    function recordCreateUrl(string $resource): string
    {
        return \Framework\Admin\AdminResourceController::createUrl($resource);
    }
}

if (!function_exists('recordIndexUrl')) {
    function recordIndexUrl(string $resource): string
    {
        return \Framework\Admin\AdminResourceController::indexUrl($resource);
    }
}

if (!function_exists('recordCustomUrl')) {
    function recordCustomUrl(string $resource, string $action): string
    {
        return \Framework\Admin\AdminResourceController::customUrl($resource, $action);
    }
}

if (!function_exists('recordCustomRecordUrl')) {
    function recordCustomRecordUrl(string $resource, mixed $id, string $action): string
    {
        return \Framework\Admin\AdminResourceController::customRecordUrl($resource, $id, $action);
    }
}
