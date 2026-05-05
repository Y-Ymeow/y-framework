<?php

declare(strict_types=1);

namespace Framework\Database\Contracts;

interface ModelInterface
{
    public function getKey(): mixed;

    public function getKeyName(): string;

    public function getTable(): string;

    public function getConnection(): ConnectionInterface;

    public function fill(array $attributes): self;

    public function toArray(): array;

    public function fresh(): ?static;

    public function refresh(): static;

    public function exists(): bool;
}