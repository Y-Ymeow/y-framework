<?php

declare(strict_types=1);

namespace Framework\Lifecycle;

class ServiceCollector implements CollectorInterface
{
    private array $services = [];
    private array $singletons = [];

    public function collect(array $items): void
    {
        foreach ($items as $name => $item) {
            if (is_string($item)) {
                $this->register($name, $item);
            } elseif (is_array($item)) {
                $this->register($name, $item['class'] ?? '', $item['singleton'] ?? false, $item['alias'] ?? null);
            }
        }
    }

    public function register(string $name, string $class, bool $singleton = false, ?string $alias = null): void
    {
        $this->services[$name] = [
            'class' => $class,
            'name' => $name,
            'singleton' => $singleton,
            'alias' => $alias,
        ];

        if ($singleton) {
            $this->singletons[] = $name;
        }
    }

    public function getCollected(): array
    {
        return $this->services;
    }

    public function getByName(string $name): ?array
    {
        return $this->services[$name] ?? null;
    }

    public function getSingletons(): array
    {
        return array_filter($this->services, fn($s) => $s['singleton']);
    }

    public function getByClass(string $class): ?array
    {
        foreach ($this->services as $name => $service) {
            if ($service['class'] === $class) {
                return $service;
            }
        }
        return null;
    }

    public function clear(): void
    {
        $this->services = [];
        $this->singletons = [];
    }

    public function count(): int
    {
        return count($this->services);
    }
}
