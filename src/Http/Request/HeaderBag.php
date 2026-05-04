<?php

declare(strict_types=1);

namespace Framework\Http\Request;

/**
 * 请求头容器
 */
class HeaderBag
{
    private array $headers = [];

    /**
     * @param array<string, string> $headers
     */
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }

    /**
     * 从 $_SERVER 解析 HTTP 头
     */
    public static function fromServer(array $server): self
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerKey = str_replace('_', '-', substr($key, 5));
                $headers[$headerKey] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['CONTENT-TYPE'] = $server['CONTENT_TYPE'];
        }
        if (isset($server['CONTENT_LENGTH'])) {
            $headers['CONTENT-LENGTH'] = $server['CONTENT_LENGTH'];
        }

        return new self($headers);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $normalizedKey = $this->normalize($key);

        if (isset($this->headers[$normalizedKey])) {
            return $this->headers[$normalizedKey];
        }

        foreach ($this->headers as $headerKey => $value) {
            if ($this->normalize($headerKey) === $normalizedKey) {
                return $value;
            }
        }

        return $default;
    }

    public function set(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    public function all(): array
    {
        return $this->headers;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * 判断是否为 JSON 请求
     */
    public function isJson(): bool
    {
        $ct = $this->get('Content-Type', '');
        return str_contains($ct, 'application/json');
    }

    /**
     * 判断客户端是否期望 JSON
     */
    public function expectsJson(): bool
    {
        $accept = $this->get('Accept', '');
        $isAjax = $this->get('X-Requested-With') === 'XMLHttpRequest';
        return str_contains($accept, 'application/json') || $isAjax;
    }

    public function isAjax(): bool
    {
        return $this->get('X-Requested-With') === 'XMLHttpRequest';
    }

    private function normalize(string $key): string
    {
        return strtoupper(str_replace('-', '_', $key));
    }
}