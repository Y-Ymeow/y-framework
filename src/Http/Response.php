<?php

declare(strict_types=1);

namespace Framework\Http;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $content = '',
        private readonly int $status = 200,
        private readonly array $headers = ['Content-Type' => 'text/html; charset=UTF-8'],
    ) {
    }

    public static function json(array $data, int $status = 200): self
    {
        return new self(
            (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->content;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function status(): int
    {
        return $this->status;
    }
}
