<?php

declare(strict_types=1);

namespace Framework\Http;

use Framework\Http\Cookie\CookieJarNan;
use Framework\Http\Cookie\Cookie as CookieValue;

/**
 * Cookie 门面（向后兼容层）
 *
 * 提供静态方法以兼容现有代码，内部委托给 CookieJar。
 * 新代码请直接使用 Framework\Http\Cookie\CookieJar。
 *
 * @deprecated 推荐使用 Cookie\CookieJar（实例化）或 Cookie\Cookie（值对象）
 */
class Cookie
{
    /**
     * 创建一个 CookieJar 实例（从全局 $_COOKIE 填充）
     */
    public static function jar(): CookieJar
    {
        return new CookieJar($_COOKIE);
    }

    /**
     * 发送一个 Cookie（立即发送 setcookie）
     */
    public static function set(string $name, string $value, int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true, string $sameSite = 'Lax'): void
    {
        $options = [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ];
        setcookie($name, $value, $options);
    }

    /**
     * 读取请求 Cookie
     */
    public static function get(string $name, ?string $default = null): ?string
    {
        return $_COOKIE[$name] ?? $default;
    }

    /**
     * 检查 Cookie 是否存在
     */
    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * 移除 Cookie（立即发送过期 setcookie）
     */
    public static function remove(string $name, string $path = '/', string $domain = ''): void
    {
        setcookie($name, '', time() - 3600, $path, $domain);
        unset($_COOKIE[$name]);
    }

    /**
     * 设置持久 Cookie（5 年有效期）
     */
    public static function forever(string $name, string $value, string $path = '/', string $domain = '', bool $secure = true, bool $httpOnly = true, string $sameSite = 'Lax'): void
    {
        self::set($name, $value, time() + 5 * 365 * 24 * 60 * 60, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    /**
     * 移除 Cookie（forget = remove 别名）
     */
    public static function forget(string $name, string $path = '/', string $domain = ''): void
    {
        self::remove($name, $path, $domain);
    }
}