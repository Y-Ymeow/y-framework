<?php

declare(strict_types=1);

namespace Framework\Routing;

class RouteCollection
{
    private array $routes = [];
    private array $byMethod = [];
    private array $byName = [];

    public function add(Route $route): void
    {
        $this->routes[] = $route;
        $this->byMethod[$route->getMethod()][] = $route;
        $this->byName[$route->getName()] = $route;
    }

    public function getByName(string $name): ?Route
    {
        return $this->byName[$name] ?? null;
    }

    public function getByMethod(string $method): array
    {
        return $this->byMethod[strtoupper($method)] ?? [];
    }

    public function all(): array
    {
        return $this->routes;
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function merge(self $other): void
    {
        foreach ($other->all() as $route) {
            $this->add($route);
        }
    }

    public function toArray(): array
    {
        return array_map(fn(Route $r) => $r->toArray(), $this->routes);
    }

    public static function fromArray(array $data): self
    {
        $collection = new self();
        foreach ($data as $item) {
            $collection->add(Route::fromArray($item));
        }
        return $collection;
    }
}
