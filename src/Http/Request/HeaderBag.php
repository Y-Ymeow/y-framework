<?php

declare(strict_types=1);

namespace Framework\Http\Request;

class HeaderBag
{
    private array $headers = [];

    public function __construct(array $headers = [])
    {
        foreach ($headers as $key => $value) {
            $normalizedKey = $this->normalize($key);
            $this->headers[$normalizedKey] = $value;
        }
    }

    public static function fromServer(array $server): self
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerKey = strtoupper(str_replace('_', '-', substr($key, 5)));
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
        return $this->headers[$normalizedKey] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $normalizedKey = $this->normalize($key);
        $this->headers[$normalizedKey] = $value;
    }

    public function all(): array
    {
        return $this->headers;
    }

    public function has(string $key): bool
    {
        return isset($this->headers[$this->normalize($key)]);
    }

    public function isJson(): bool
    {
        $ct = $this->get('Content-Type', '');
        return str_contains($ct, 'application/json');
    }

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
