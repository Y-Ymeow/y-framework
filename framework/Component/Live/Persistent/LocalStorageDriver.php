<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

/**
 * LocalStorage 驱动（前端）
 *
 * 数据存储在浏览器 localStorage 中，持久化在浏览器关闭后仍然存在。
 * 需要前端 JS 配合读写。
 */
class LocalStorageDriver implements PersistentDriverInterface
{
    private static array $storage = [];

    public function get(string $key): mixed
    {
        if (isset(self::$storage[$key])) {
            $data = self::$storage[$key];

            if (isset($data['expires']) && $data['expires'] < time()) {
                $this->forget($key);
                return null;
            }

            return $data['value'];
        }

        return null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $data = [
            'value' => $value,
            'expires' => $ttl ? time() + $ttl : null,
        ];

        self::$storage[$key] = $data;
        return true;
    }

    public function forget(string $key): bool
    {
        unset(self::$storage[$key]);
        return true;
    }

    public function has(string $key): bool
    {
        if (!isset(self::$storage[$key])) {
            return false;
        }

        $data = self::$storage[$key];
        if (isset($data['expires']) && $data['expires'] < time()) {
            $this->forget($key);
            return false;
        }

        return true;
    }

    /**
     * 获取所有存储的数据（用于序列化到前端）
     */
    public static function getAll(): array
    {
        return self::$storage;
    }

    /**
     * 从前端数据恢复存储
     */
    public static function loadFromFrontend(array $data): void
    {
        foreach ($data as $key => $value) {
            self::$storage[$key] = [
                'value' => $value,
                'expires' => null,
            ];
        }
    }
}
