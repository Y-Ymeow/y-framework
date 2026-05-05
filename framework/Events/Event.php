<?php

declare(strict_types=1);

namespace Framework\Events;

class Event
{
    private string $name;
    private array $payload;
    private float $timestamp;
    private bool $propagationStopped = false;

    public function __construct(string $name = '', array $payload = [])
    {
        $this->name = $name;
        $this->payload = $payload;
        $this->timestamp = microtime(true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }

    public function set(string $key, mixed $value): static
    {
        $this->payload[$key] = $value;
        return $this;
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
