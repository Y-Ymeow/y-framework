<?php

declare(strict_types=1);

namespace Framework\Events;

use Closure;

class Hook
{
    private static ?self $instance = null;
    private array $actions = [];
    private array $filters = [];
    private array $fired = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Add an action (Hook)
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        self::getInstance()->addInstanceAction($hook, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add a filter
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        self::getInstance()->addInstanceFilter($hook, $callback, $priority, $acceptedArgs);
    }

    /**
     * Alias for addAction
     */
    public static function bind(string $hook, callable $callback, int $priority = 10): void
    {
        self::addAction($hook, $callback, $priority);
    }

    /**
     * Fire an action
     */
    public static function fire(string $hook, mixed ...$args): void
    {
        self::getInstance()->fireInstanceAction($hook, ...$args);
    }

    /**
     * Apply filters to a value
     */
    public static function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return self::getInstance()->applyInstanceFilters($hook, $value, ...$args);
    }

    // Instance Methods

    public function addInstanceAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $this->actions[$hook][$priority][] = [
            'callback' => $callback,
            'accepted_args' => $acceptedArgs,
        ];
        uksort($this->actions[$hook], fn($a, $b) => $a <=> $b);
    }

    public function addInstanceFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $this->filters[$hook][$priority][] = [
            'callback' => $callback,
            'accepted_args' => $acceptedArgs,
        ];
        uksort($this->filters[$hook], fn($a, $b) => $a <=> $b);
    }

    public function fireInstanceAction(string $hook, mixed ...$args): void
    {
        $this->fired[$hook] = true;
        if (empty($this->actions[$hook])) return;

        foreach ($this->actions[$hook] as $priority => $listeners) {
            foreach ($listeners as $listener) {
                $argsToPass = array_slice($args, 0, $listener['accepted_args']);
                call_user_func_array($listener['callback'], $argsToPass);
            }
        }
    }

    public function applyInstanceFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (empty($this->filters[$hook])) return $value;

        foreach ($this->filters[$hook] as $priority => $listeners) {
            foreach ($listeners as $listener) {
                $argsToPass = array_merge([$value], array_slice($args, 0, max(0, $listener['accepted_args'] - 1)));
                $value = call_user_func_array($listener['callback'], $argsToPass);
            }
        }

        return $value;
    }

    public function hasActionInstance(string $hook): bool
    {
        return !empty($this->actions[$hook]);
    }

    public static function hasAction(string $hook): bool
    {
        return self::getInstance()->hasActionInstance($hook);
    }

    public function hasFilterInstance(string $hook): bool
    {
        return !empty($this->filters[$hook]);
    }

    public static function hasFilter(string $hook): bool
    {
        return self::getInstance()->hasFilterInstance($hook);
    }

    public function isFired(string $hook): bool
    {
        return !empty($this->fired[$hook]);
    }

    public static function fired(string $hook): bool
    {
        return self::getInstance()->isFired($hook);
    }

    public function removeInstanceAction(string $hook, ?callable $callback = null, ?int $priority = null): void
    {
        if ($callback === null && $priority === null) {
            unset($this->actions[$hook]);
            return;
        }

        if (empty($this->actions[$hook])) return;

        if ($priority !== null && isset($this->actions[$hook][$priority])) {
            if ($callback === null) {
                unset($this->actions[$hook][$priority]);
            } else {
                foreach ($this->actions[$hook][$priority] as $key => $listener) {
                    if ($listener['callback'] === $callback) {
                        unset($this->actions[$hook][$priority][$key]);
                        break;
                    }
                }
                if (empty($this->actions[$hook][$priority])) {
                    unset($this->actions[$hook][$priority]);
                }
            }
        } else {
            foreach ($this->actions[$hook] as $p => $listeners) {
                foreach ($listeners as $key => $listener) {
                    if ($listener['callback'] === $callback) {
                        unset($this->actions[$hook][$p][$key]);
                        break;
                    }
                }
                if (empty($this->actions[$hook][$p])) {
                    unset($this->actions[$hook][$p]);
                }
            }
        }

        if (empty($this->actions[$hook])) {
            unset($this->actions[$hook]);
        }
    }

    public static function removeAction(string $hook, ?callable $callback = null, ?int $priority = null): void
    {
        self::getInstance()->removeInstanceAction($hook, $callback, $priority);
    }

    public static function removeFilter(string $hook, ?callable $callback = null, ?int $priority = null): void
    {
        self::getInstance()->removeInstanceAction($hook, $callback, $priority);
    }

    public function clearHook(string $hook): void
    {
        unset($this->actions[$hook], $this->filters[$hook], $this->fired[$hook]);
    }

    public static function clear(string $hook): void
    {
        self::getInstance()->clearHook($hook);
    }

    public function resetHooks(): void
    {
        $this->actions = [];
        $this->filters = [];
        $this->fired = [];
    }

    public static function reset(): void
    {
        self::getInstance()->resetHooks();
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public static function getAllActions(): array
    {
        return self::getInstance()->getActions();
    }

    public static function getAllFilters(): array
    {
        return self::getInstance()->getFilters();
    }
}
