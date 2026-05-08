<?php

declare(strict_types=1);

namespace Framework\Cache\Drivers;

use Framework\Cache\Contracts\LockInterface;
use Framework\Cache\Contracts\StoreInterface;
use Framework\Cache\Lock\FileLock;
use Framework\Cache\Support\KeyValidator;
use Framework\Cache\Support\Serialization;
use Framework\Cache\Support\TtlHelper;

class FileDriver implements StoreInterface
{
    private string $path;
    private string $prefix;
    private int $gcProbability;
    private int $gcDivisor;

    public function __construct(array $config)
    {
        $this->path = rtrim($config['path'] ?? sys_get_temp_dir() . '/cache', '/');
        $this->prefix = $config['prefix'] ?? '';
        $this->gcProbability = $config['gc_probability'] ?? 10;
        $this->gcDivisor = $config['gc_divisor'] ?? 100;

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        KeyValidator::validate($key);

        $this->gc();

        $file = $this->filePath($key);
        if (!is_file($file)) {
            return $default;
        }

        $entry = Serialization::decodeEntry(file_get_contents($file));
        if ($entry === null) {
            return $default;
        }

        if (Serialization::isExpired($entry['expires'])) {
            $this->delete($key);
            return $default;
        }

        return $entry['value'];
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        KeyValidator::validate($key);

        $expires = TtlHelper::resolveTtl($ttl);
        $file = $this->filePath($key);

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($file, Serialization::encodeEntry($value, $expires), LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        KeyValidator::validate($key);

        $file = $this->filePath($key);
        if (is_file($file)) {
            return unlink($file);
        }
        return true;
    }

    public function has(string $key): bool
    {
        KeyValidator::validate($key);

        $file = $this->filePath($key);
        if (!is_file($file)) {
            return false;
        }

        $entry = Serialization::decodeEntry(file_get_contents($file));
        if ($entry === null) {
            return false;
        }

        if (Serialization::isExpired($entry['expires'])) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    public function clear(): bool
    {
        $this->clearDirectory($this->path);
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        KeyValidator::validateMultiple($keys);

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }
        return true;
    }

    public function increment(string $key, int $step = 1): int
    {
        KeyValidator::validate($key);

        $current = $this->has($key) ? (int) $this->get($key) : 0;
        $newValue = $current + $step;
        $this->set($key, $newValue);
        return $newValue;
    }

    public function decrement(string $key, int $step = 1): int
    {
        KeyValidator::validate($key);

        $current = $this->has($key) ? (int) $this->get($key) : 0;
        $newValue = $current - $step;
        $this->set($key, $newValue);
        return $newValue;
    }

    public function lock(string $key, int $seconds = 0): LockInterface
    {
        return new FileLock($key, $seconds, $this->path . '/locks');
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function gc(): int
    {
        if (mt_rand(1, $this->gcDivisor) > $this->gcProbability) {
            return 0;
        }

        return $this->gcForce();
    }

    public function gcForce(): int
    {
        $count = 0;
        $this->walkDirectory($this->path, function (string $file) use (&$count): void {
            $entry = Serialization::decodeEntry(@file_get_contents($file));
            if ($entry === null || Serialization::isExpired($entry['expires'])) {
                @unlink($file);
                $count++;
            }
        });
        return $count;
    }

    private function filePath(string $key): string
    {
        $hash = md5($this->prefix . $key);
        return $this->path . '/' . substr($hash, 0, 2) . '/' . $hash;
    }

    private function clearDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isFile()) {
                @unlink($item->getRealPath());
            } elseif ($item->isDir()) {
                @rmdir($item->getRealPath());
            }
        }
    }

    private function walkDirectory(string $dir, callable $callback): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $callback($file->getRealPath());
            }
        }
    }
}
