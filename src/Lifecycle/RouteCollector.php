<?php

declare(strict_types=1);

namespace Framework\Lifecycle;

class RouteCollector implements CollectorInterface
{
    private array $routes = [];
    private array $groups = [];

    public function collect(array $items): void
    {
        foreach ($items as $item) {
            $this->addRoute($item);
        }
    }

    public function addRoute(array $route): void
    {
        $defaults = [
            'method' => 'GET',
            'path' => '',
            'handler' => null,
            'name' => '',
            'middleware' => [],
            'group' => null,
        ];
        $route = array_merge($defaults, $route);
        $route['name'] = $route['name'] ?: ($route['method'] . ':' . $route['path']);
        $this->routes[] = $route;

        if ($route['group'] !== null) {
            $this->groups[$route['group']][] = $route;
        }
    }

    public function getCollected(): array
    {
        return $this->routes;
    }

    public function getByMethod(string $method): array
    {
        return array_filter($this->routes, fn($r) => strtoupper($r['method']) === strtoupper($method));
    }

    public function getByName(string $name): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['name'] === $name) {
                return $route;
            }
        }
        return null;
    }

    public function getByGroup(string $group): array
    {
        return $this->groups[$group] ?? [];
    }

    public function clear(): void
    {
        $this->routes = [];
        $this->groups = [];
    }

    public function count(): int
    {
        return count($this->routes);
    }

    public function merge(RouteCollector $other): self
    {
        foreach ($other->getCollected() as $route) {
            $this->addRoute($route);
        }
        return $this;
    }
}
