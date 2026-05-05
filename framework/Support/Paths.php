<?php

declare(strict_types=1);

namespace Framework\Support;

class Paths
{
    private string $basePath;
    private string $storagePath;
    private string $publicPath;
    private string $configPath;
    private string $resourcesPath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->storagePath = $this->basePath . '/storage';
        $this->publicPath = $this->basePath . '/public';
        $this->configPath = $this->basePath . '/config';
        $this->resourcesPath = $this->basePath . '/resources';
    }

    public function base(string $path = ''): string
    {
        return $path ? $this->basePath . '/' . ltrim($path, '/') : $this->basePath;
    }

    public function storage(string $path = ''): string
    {
        return $path ? $this->storagePath . '/' . ltrim($path, '/') : $this->storagePath;
    }

    public function public(string $path = ''): string
    {
        return $path ? $this->publicPath . '/' . ltrim($path, '/') : $this->publicPath;
    }

    public function config(string $path = ''): string
    {
        return $path ? $this->configPath . '/' . ltrim($path, '/') : $this->configPath;
    }

    public function resources(string $path = ''): string
    {
        return $path ? $this->resourcesPath . '/' . ltrim($path, '/') : $this->resourcesPath;
    }

    public function cache(string $path = ''): string
    {
        return $this->storage('cache' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function logs(string $path = ''): string
    {
        return $this->storage('logs' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function sessions(string $path = ''): string
    {
        return $this->storage('sessions' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function uploads(string $path = ''): string
    {
        return $this->storage('uploads' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function files(string $path = ''): string
    {
        return $this->storage('files' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function debug(string $path = ''): string
    {
        return $this->storage('debug' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function sse(string $path = ''): string
    {
        return $this->storage('sse' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function runtimeConfig(string $path = ''): string
    {
        return $this->storage('config' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function database(string $path = ''): string
    {
        return $this->basePath . '/database' . ($path ? '/' . ltrim($path, '/') : '');
    }

    public function migrations(string $path = ''): string
    {
        return $this->database('migrations' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function seeders(string $path = ''): string
    {
        return $this->database('seeders' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function routes(string $path = ''): string
    {
        return $this->basePath . '/routes' . ($path ? '/' . ltrim($path, '/') : '');
    }

    public function lang(string $path = ''): string
    {
        return $this->resources('lang' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function views(string $path = ''): string
    {
        return $this->resources('views' . ($path ? '/' . ltrim($path, '/') : ''));
    }

    public function app(string $path = ''): string
    {
        return $this->basePath . '/app' . ($path ? '/' . ltrim($path, '/') : '');
    }

    public function frameworkSrc(string $path = ''): string
    {
        return dirname(__DIR__) . ($path ? '/' . ltrim($path, '/') : '');
    }

    public function setStoragePath(string $path): void
    {
        $this->storagePath = rtrim($path, '/');
    }

    public function setPublicPath(string $path): void
    {
        $this->publicPath = rtrim($path, '/');
    }
}
