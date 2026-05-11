<?php

declare(strict_types=1);

namespace Framework\Foundation;

use Admin\Services\AdminManager;
use Framework\Http\Request\Request;

class AppContext
{
    public const MODE_ADMIN = 'admin';
    public const MODE_FRONTEND = 'frontend';
    public const MODE_CLI = 'cli';

    private static ?string $cachedMode = null;

    public static function reset(): void
    {
        self::$cachedMode = null;
    }

    public static function mode(): string
    {
        if (self::$cachedMode !== null) {
            return self::$cachedMode;
        }

        if (PHP_SAPI === 'cli') {
            return self::$cachedMode = self::MODE_CLI;
        }

        try {
            $request = app()->make(Request::class);
            $prefix = AdminManager::getPrefix();
            $path = '/' . trim($request->path(), '/');

            if ($prefix !== '' && str_starts_with($path, $prefix)) {
                return self::$cachedMode = self::MODE_ADMIN;
            }
        } catch (\Throwable) {
            return self::$cachedMode = self::MODE_FRONTEND;
        }

        return self::$cachedMode = self::MODE_FRONTEND;
    }

    public static function isAdmin(): bool
    {
        return self::mode() === self::MODE_ADMIN;
    }

    public static function isFrontend(): bool
    {
        return self::mode() === self::MODE_FRONTEND;
    }

    public static function isCli(): bool
    {
        return self::mode() === self::MODE_CLI;
    }
}