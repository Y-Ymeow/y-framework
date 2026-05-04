<?php

declare(strict_types=1);

namespace Framework\Component\Live\Persistent;

class SessionStorageDriver implements PersistentDriverInterface
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

    public static function getAll(): array
    {
        return self::$storage;
    }

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
