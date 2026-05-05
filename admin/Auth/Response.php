<?php

declare(strict_types=1);

namespace Admin\Auth;

class Response
{
    private bool $allowed;
    private ?string $message;
    private mixed $code;

    public function __construct(bool $allowed, ?string $message = null, mixed $code = null)
    {
        $this->allowed = $allowed;
        $this->message = $message;
        $this->code = $code;
    }

    public function allowed(): bool
    {
        return $this->allowed;
    }

    public function denied(): bool
    {
        return !$this->allowed;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function code(): mixed
    {
        return $this->code;
    }

    public static function allow(?string $message = null): self
    {
        return new self(true, $message);
    }

    public static function deny(string $message = 'Unauthorized.', mixed $code = 403): self
    {
        return new self(false, $message, $code);
    }
}
