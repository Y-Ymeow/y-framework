<?php

declare(strict_types=1);

namespace Framework\Http\Cookie;

/**
 * Cookie 值对象
 * 
 * 表示一个 HTTP Cookie，不负责发送或存储。
 */
class Cookie
{
    public function __construct(
        private readonly string $name,
        private readonly string $value = '',
        private readonly int $expiresAt = 0,
        private readonly string $path = '/',
        private readonly ?string $domain = null,
        private readonly bool $secure = false,
        private readonly bool $httpOnly = true,
        private readonly ?string $sameSite = 'Lax',
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    /**
     * Cookie 是否已过期
     */
    public function isExpired(): bool
    {
        return $this->expiresAt > 0 && $this->expiresAt < time();
    }

    /**
     * 转换为 set-cookie 头字符串
     */
    public function toHeaderValue(): string
    {
        $parts = [rawurlencode($this->name) . '=' . rawurlencode($this->value)];

        if ($this->expiresAt > 0) {
            $parts[] = 'Expires=' . gmdate('D, d-M-Y H:i:s T', $this->expiresAt);
        }

        $parts[] = 'Max-Age=' . max(0, $this->expiresAt - time());
        $parts[] = 'Path=' . $this->path;

        if ($this->domain !== null) {
            $parts[] = 'Domain=' . $this->domain;
        }

        if ($this->secure) {
            $parts[] = 'Secure';
        }

        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($this->sameSite !== null) {
            $parts[] = 'SameSite=' . $this->sameSite;
        }

        return implode('; ', $parts);
    }

    // ── 工厂方法 ──

    /**
     * 创建一个会话 Cookie（浏览器关闭即过期）
     */
    public static function session(string $name, string $value, array $options = []): self
    {
        return new self($name, $value, 0, ...self::extractOptions($options));
    }

    /**
     * 创建一个持久 Cookie（指定过期时间）
     */
    public static function forever(string $name, string $value, ?int $lifetime = null, array $options = []): self
    {
        $lifetime ??= 5 * 365 * 24 * 3600; // 默认 5 年
        return new self($name, $value, time() + $lifetime, ...self::extractOptions($options));
    }

    /**
     * 创建一个过期的 Cookie（用于删除）
     */
    public static function expired(string $name, array $options = []): self
    {
        return new self($name, '', time() - 3600, ...self::extractOptions($options));
    }

    /**
     * 从请求 header 解析 Cookie
     */
    public static function fromHeaderValue(string $name, string $rawValue): self
    {
        $decoded = rawurldecode($rawValue);
        return new self($name, $decoded);
    }

    private static function extractOptions(array $options): array
    {
        return [
            'path' => $options['path'] ?? '/',
            'domain' => $options['domain'] ?? null,
            'secure' => $options['secure'] ?? false,
            'httpOnly' => $options['httpOnly'] ?? true,
            'sameSite' => $options['sameSite'] ?? 'Lax',
        ];
    }
}