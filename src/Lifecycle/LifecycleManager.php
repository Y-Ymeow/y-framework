<?php

declare(strict_types=1);

namespace Framework\Lifecycle;

use Framework\Events\Hook;

class LifecycleManager
{
    private static ?self $instance = null;
    private array $collectors = [];
    private bool $booted = false;
    private array $pendingRegistrations = [];
    private ?AttributeScanner $scanner = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
        Hook::reset();
    }

    public function __construct()
    {
    }

    public function registerCollector(string $name, CollectorInterface $collector): void
    {
        $this->collectors[$name] = $collector;
        Hook::fire('collector.registered', $name, $collector);
    }

    public function getCollector(string $name): ?CollectorInterface
    {
        return $this->collectors[$name] ?? null;
    }

    public function registerRoute(array $route): void
    {
        Hook::fire('routes.registering', $route);

        $collector = $this->getCollector('routes');
        if ($collector === null) {
            $this->pendingRegistrations['routes'][] = $route;
            return;
        }

        if ($collector instanceof RouteCollector) {
            $collector->addRoute($route);
            Hook::fire('routes.registered', $route);
        }
    }

    public function registerComponent(array $component): void
    {
        Hook::fire('components.registering', $component);

        $collector = $this->getCollector('components');
        if ($collector === null) {
            $this->pendingRegistrations['components'][] = $component;
            return;
        }

        if ($collector instanceof ComponentCollector) {
            $collector->addComponent($component);
            Hook::fire('components.registered', $component);
        }
    }

    public function registerService(string $name, string $class, bool $singleton = false, ?string $alias = null): void
    {
        Hook::fire('services.registering', $name, $class, $singleton);

        $collector = $this->getCollector('services');
        if ($collector === null) {
            $this->pendingRegistrations['services'][] = [
                'name' => $name,
                'class' => $class,
                'singleton' => $singleton,
                'alias' => $alias,
            ];
            return;
        }

        if ($collector instanceof ServiceCollector) {
            $collector->register($name, $class, $singleton, $alias);
            Hook::fire('services.registered', $name, $class, $singleton);
        }
    }

    public function flushPending(): void
    {
        foreach ($this->pendingRegistrations as $type => $items) {
            $collector = $this->getCollector($type);
            if ($collector !== null) {
                $collector->collect($items);
                Hook::fire("{$type}.flushed", $items);
            }
        }
        $this->pendingRegistrations = [];
    }

    public function scanAttributes(string|array $directories): void
    {
        if ($this->scanner === null) {
            $this->scanner = new AttributeScanner($this);
        }
        $this->scanner->scan($directories);
    }

    public function boot(): void
    {
        if ($this->booted) return;

        Hook::fire('app.booting');
        $this->flushPending();
        Hook::fire('app.booted');

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function bindHook(string $hook, callable $callback, int $priority = 10): void
    {
        Hook::bind($hook, $callback, $priority);
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        Hook::addFilter($hook, $callback, $priority);
    }

    public function getSummary(): array
    {
        $summary = [];
        foreach ($this->collectors as $name => $collector) {
            $summary[$name] = [
                'count' => $collector->count(),
                'items' => $collector->getCollected(),
            ];
        }
        return $summary;
    }

    public function getGlobalHooks(): array
    {
        return [
            'app.initializing',
            'app.initialized',
            'app.booting',
            'app.booted',
            'routes.registering',
            'routes.registered',
            'routes.scanning',
            'routes.scanned',
            'services.registering',
            'services.registered',
            'components.registering',
            'components.registered',
            'request.received',
            'response.created',
            'response.sending',
            'response.sent',
        ];
    }

    public function fire(string $hook, mixed ...$args): void
    {
        Hook::fire($hook, ...$args);
    }

    public function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return Hook::filter($hook, $value, ...$args);
    }
}
