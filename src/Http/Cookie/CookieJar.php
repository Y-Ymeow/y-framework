<?php

declare(strict_types=1);

namespace Framework\Http\Cookie;

/**
 * Cookie 集合 —— 请求 Cookie 读取 + 响应 Cookie 设置
 *
 * 请求阶段：从 Request 构造，提供只读访问
 * 响应阶段：通过 set/remove/forget 追加待发送 Cookie
 */
class CookieJar
{
    /** @var array<string, Cookie> 收到的请求 Cookie */
    private array $cookies = [];

    /** @var array<string, Cookie> 待发送的响应 Cookie */
    private array $queued = [];

    /**
     * @param array<string, string> $cookies 从请求中解析的原始键值对
     */
    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $name => $value) {
            $this->cookies[$name] = Cookie::fromHeaderValue($name, $value);
        }
    }

    // ── 请求端（只读） ──

    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    public function get(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name]?->getValue() ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $result = [];
        foreach ($this->cookies as $name => $cookie) {
            $result[$name] = $cookie->getValue();
        }
        return $result;
    }

    // ── 响应端（追加待发送 Cookie） ──

    public function set(Cookie $cookie): void
    {
        $this->queued[$cookie->getName()] = $cookie;
    }

    /**
     * 便捷方式设置 Cookie
     */
    public function setValue(string $name, string $value, array $options = []): void
    {
        $this->set(Cookie::session($name, $value, $options));
    }

    /**
     * 设置持久 Cookie
     */
    public function forever(string $name, string $value, ?int $lifetime = null, array $options = []): void
    {
        $this->set(Cookie::forever($name, $value, $lifetime, $options));
    }

    /**
     * 移除 Cookie（通过发送过期 Cookie）
     */
    public function remove(string $name, array $options = []): void
    {
        $this->set(Cookie::expired($name, $options));
    }

    /**
     * remove 的别名
     */
    public function forget(string $name, array $options = []): void
    {
        $this->remove($name, $options);
    }

    // ── 发送 ──

    /**
     * 获取所有待发送的 Cookie
     * @return array<string, Cookie>
     */
    public function getQueued(): array
    {
        return $this->queued;
    }

    /**
     * 是否有待发送的 Cookie
     */
    public function hasQueued(): bool
    {
        return $this->queued !== [];
    }

    /**
     * 清空排队队列
     */
    public function flush(): void
    {
        $this->queued = [];
    }

    /**
     * 将待发送 Cookie 写入响应头
     *
     * @param callable(string $headerValue): void $emitter 如 header()
     */
    public function send(callable $emitter): void
    {
        foreach ($this->queued as $cookie) {
            $emitter($cookie->toHeaderValue());
        }
    }
}