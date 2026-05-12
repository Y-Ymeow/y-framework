<?php

declare(strict_types=1);

namespace Framework\Lifecycle;

use Framework\Events\CollectorsFlushedEvent;
use Framework\Events\Hook;
use Framework\Events\BootEvent;
use Framework\Events\CollectorRegisteredEvent;
use Framework\Events\RouteRegisteringEvent;
use Framework\Events\RouteRegisteredEvent;
use Framework\Events\ComponentRegisteringEvent;
use Framework\Events\ComponentRegisteredEvent;
use Framework\Events\ServiceRegisteringEvent;
use Framework\Events\ServiceRegisteredEvent;
use Framework\Events\ScheduleRegisteringEvent;

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
        Hook::getInstance()->dispatch(new CollectorRegisteredEvent($name, $collector));
    }

    public function getCollector(string $name): ?CollectorInterface
    {
        return $this->collectors[$name] ?? null;
    }

    public function registerRoute(array $route): void
    {
        Hook::getInstance()->dispatch(new RouteRegisteringEvent($route));

        $collector = $this->getCollector('routes');
        if ($collector === null) {
            $this->pendingRegistrations['routes'][] = $route;
            return;
        }

        if ($collector instanceof RouteCollector) {
            $collector->addRoute($route);
            Hook::getInstance()->dispatch(new RouteRegisteredEvent($route));
        }
    }

    public function registerComponent(array $component): void
    {
        Hook::getInstance()->dispatch(new ComponentRegisteringEvent($component));

        $collector = $this->getCollector('components');
        if ($collector === null) {
            $this->pendingRegistrations['components'][] = $component;
            return;
        }

        if ($collector instanceof ComponentCollector) {
            $collector->addComponent($component);
            Hook::getInstance()->dispatch(new ComponentRegisteredEvent($component));
        }
    }

    public function registerService(string $name, string $class, bool $singleton = false, ?string $alias = null): void
    {
        Hook::getInstance()->dispatch(new ServiceRegisteringEvent($name, $class, $singleton));

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
            Hook::getInstance()->dispatch(new ServiceRegisteredEvent($name, $class, $singleton));
        }
    }

    public function registerSchedule(array $schedule): void
    {
        Hook::getInstance()->dispatch(new ScheduleRegisteringEvent($schedule));

        $scheduler = app()->make(\Framework\Scheduler\Scheduler::class);
        if ($scheduler) {
            $scheduler->call(function() use ($schedule) {
                $instance = app()->make($schedule['class']);
                return app()->call([$instance, $schedule['method']]);
            })->cron($schedule['expression']);
        }
    }

    public function flushPending(): void
    {
        foreach ($this->pendingRegistrations as $type => $items) {
            $collector = $this->getCollector($type);
            if ($collector !== null) {
                $collector->collect($items);
                Hook::getInstance()->dispatch(new CollectorsFlushedEvent($type, $items));
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

        Hook::getInstance()->dispatch(new BootEvent('app.booting'));
        $this->flushPending();
        Hook::getInstance()->dispatch(new BootEvent('app.booted'));

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function bindHook(string $hook, callable $callback, int $priority = 10): void
    {
        Hook::getInstance()->on($hook, $callback, $priority);
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        Hook::getInstance()->on($hook, $callback, $priority);
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
        Hook::getInstance()->emit($hook, $args);
    }

    public function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return Hook::applyFilter($hook, $value, ...$args);
    }
}
