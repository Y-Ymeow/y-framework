<?php

declare(strict_types=1);

namespace Framework\Cache\Lock;

class ArrayLock extends Lock
{
    private static array $locks = [];

    public function acquire(): bool
    {
        if (isset(self::$locks[$this->key])) {
            if (self::$locks[$this->key]['owner'] === $this->owner) {
                return true;
            }

            if (self::$locks[$this->key]['expires'] !== null && self::$locks[$this->key]['expires'] < time()) {
                unset(self::$locks[$this->key]);
            } else {
                return false;
            }
        }

        self::$locks[$this->key] = [
            'owner' => $this->owner,
            'expires' => $this->seconds > 0 ? time() + $this->seconds : null,
        ];

        return true;
    }

    public function release(): bool
    {
        if (!$this->isOwnedByCurrentProcess()) {
            return false;
        }

        unset(self::$locks[$this->key]);
        return true;
    }

    public function isOwnedByCurrentProcess(): bool
    {
        return isset(self::$locks[$this->key]) && self::$locks[$this->key]['owner'] === $this->owner;
    }

    public static function reset(): void
    {
        self::$locks = [];
    }
}
