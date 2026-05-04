<?php

declare(strict_types=1);

namespace Framework\Http\Client;

use Framework\Exception\HttpClientException;

class HttpClientResponse
{
    public function __construct(
        private int $status,
        private string $body,
        private array $headers,
        private float $elapsed,
    ) {}

    public function status(): int { return $this->status; }
    public function body(): string { return $this->body; }
    public function headers(): array { return $this->headers; }
    public function elapsed(): float { return $this->elapsed; }

    public function json(): ?array
    {
        $data = json_decode($this->body, true);
        return is_array($data) ? $data : null;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function failed(): bool
    {
        return !$this->successful();
    }

    public function clientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    public function serverError(): bool
    {
        return $this->status >= 500;
    }

    public function header(string $key): ?string
    {
        foreach ($this->headers[$key] ?? [] as $value) {
            return $value;
        }
        return null;
    }

    public function throw(): self
    {
        if ($this->failed()) {
            throw new HttpClientException($this->status, $this->body);
        }
        return $this;
    }
}
