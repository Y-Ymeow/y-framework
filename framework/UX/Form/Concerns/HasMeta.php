<?php

declare(strict_types=1);

namespace Framework\UX\Form\Concerns;

trait HasMeta
{
    protected array $meta = [];

    public function withMeta(string $key, mixed $value): static
    {
        $this->meta[$key] = $value;
        return $this;
    }

    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    public function getAllMeta(): array
    {
        return $this->meta;
    }

    public function mergeMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }
}
