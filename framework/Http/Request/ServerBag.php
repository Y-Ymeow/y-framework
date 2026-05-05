<?php

declare(strict_types=1);

namespace Framework\Http\Request;

/**
 * 服务器变量容器
 */
class ServerBag
{
    private array $server;

    public function __construct(array $server = [])
    {
        $this->server = $server;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->server[$key] = $value;
    }

    public function all(): array
    {
        return $this->server;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->server);
    }

    // ── 常用获取方法 ──

    public function getMethod(): string
    {
        return $this->get('REQUEST_METHOD', 'GET');
    }

    public function getRequestUri(): string
    {
        return $this->get('REQUEST_URI', '/');
    }

    public function getPath(): string
    {
        $uri = $this->getRequestUri();
        $path = parse_url($uri, PHP_URL_PATH);
        return $path ?: '/';
    }

    public function getProtocol(): string
    {
        return $this->get('SERVER_PROTOCOL', 'HTTP/1.1');
    }

    public function getHost(): string
    {
        return $this->get('HTTP_HOST')
            ?? $this->get('SERVER_NAME')
            ?? 'localhost';
    }

    public function getPort(): int
    {
        return (int)($this->get('SERVER_PORT', 80));
    }

    public function isHttps(): bool
    {
        $https = $this->get('HTTPS');
        return !empty($https) && $https !== 'off';
    }

    public function getScheme(): string
    {
        return $this->isHttps() ? 'https' : 'http';
    }

    public function getIp(): string
    {
        return $this->get('HTTP_X_FORWARDED_FOR')
            ?? $this->get('HTTP_X_REAL_IP')
            ?? $this->get('REMOTE_ADDR')
            ?? '127.0.0.1';
    }

    public function getUserAgent(): string
    {
        return $this->get('HTTP_USER_AGENT', '');
    }
}