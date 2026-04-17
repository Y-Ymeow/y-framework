<?php

declare(strict_types=1);

namespace Framework\Core;

use Closure;
use Framework\Cache\FileCache;
use Framework\Config\ConfigRepository;
use RuntimeException;

final class Application
{
    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * @var array<string, Closure(self): mixed>
     */
    private array $bindings = [];

    public function __construct(
        private readonly string $basePath,
        private readonly ConfigRepository $config,
        private readonly FileCache $cache,
    ) {
        $this->instances[self::class] = $this;
        $this->instances[ConfigRepository::class] = $config;
        $this->instances[FileCache::class] = $cache;
    }

    public function basePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    public function config(): ConfigRepository
    {
        return $this->config;
    }

    public function cache(): FileCache
    {
        return $this->cache;
    }

    public function bind(string $id, Closure $resolver): void
    {
        $this->bindings[$id] = $resolver;
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function make(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (array_key_exists($id, $this->bindings)) {
            $resolved = $this->bindings[$id]($this);
            $this->instances[$id] = $resolved;

            return $resolved;
        }

        if (class_exists($id)) {
            $instance = new $id();
            $this->instances[$id] = $instance;

            return $instance;
        }

        throw new RuntimeException("Service [{$id}] is not bound.");
    }

    /**
     * 移除容器中的实例 (用于 Worker 模式状态清理)
     */
    public function forget(string $id): void
    {
        unset($this->instances[$id]);
    }
}
