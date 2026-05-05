<?php

declare(strict_types=1);

namespace Framework\Events;

class Hook implements EventDispatcherInterface
{
    private static ?self $instance = null;
    private array $listeners = [];
    private array $wildcardPatterns = [];
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

    public function on(string $eventName, callable $listener, int $priority = 0, int $acceptedArgs = -1): void
    {
        // 如果指定了 acceptedArgs，用闭包包装 listener 限制参数数量
        $callback = $listener;
        if ($acceptedArgs > 0) {
            $callback = function (...$args) use ($listener, $acceptedArgs) {
                return $listener(...array_slice($args, 0, $acceptedArgs));
            };
        }

        // 存储 listener 信息（包含原始 callback 用于移除匹配）
        $this->listeners[$eventName][$priority][] = [
            'callback' => $callback,
            'original' => $listener,
            'acceptedArgs' => $acceptedArgs,
        ];

        if (str_contains($eventName, '*')) {
            $this->wildcardPatterns[$eventName] = $this->compileWildcardPattern($eventName);
        }
    }

    public function off(string $eventName, ?callable $listener = null): void
    {
        if ($listener === null) {
            unset($this->listeners[$eventName], $this->fired[$eventName]);
            unset($this->wildcardPatterns[$eventName]);
            return;
        }

        if (empty($this->listeners[$eventName])) return;

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $key => $entry) {
                // 匹配原始 callback 或包装后的 callback
                $matched = ($entry['original'] === $listener) || ($entry['callback'] === $listener);
                if ($matched) {
                    unset($this->listeners[$eventName][$priority][$key]);
                }
            }
            if (empty($this->listeners[$eventName][$priority])) {
                unset($this->listeners[$eventName][$priority]);
            }
        }

        if (empty($this->listeners[$eventName])) {
            unset($this->listeners[$eventName]);
            unset($this->wildcardPatterns[$eventName]);
        }
    }

    public function listeners(string $eventName): array
    {
        $direct = $this->listeners[$eventName] ?? [];
        $wildcard = $this->getWildcardListeners($eventName);

        // 使用保留键的合并方式，避免 array_merge 重索引数字键导致优先级丢失
        $all = $direct;
        foreach ($wildcard as $priority => $listeners) {
            $all[$priority] = array_merge($all[$priority] ?? [], $listeners);
        }

        $sorted = [];
        foreach ($all as $priority => $entries) {
            // 提取 callback（存储结构包含 callback/original/acceptedArgs）
            foreach ($entries as $entry) {
                $callback = is_array($entry) ? $entry['callback'] : $entry;
                $sorted[$priority][] = $callback;
            }
        }

        ksort($sorted, SORT_NUMERIC);

        $result = [];
        foreach ($sorted as $callbacks) {
            foreach ($callbacks as $callback) {
                $result[] = $callback;
            }
        }
        return $result;
    }

    public function hasListeners(string $eventName): bool
    {
        if (!empty($this->listeners[$eventName])) return true;

        foreach ($this->wildcardPatterns as $pattern => $regex) {
            if (preg_match($regex, $eventName)) return true;
        }

        return false;
    }

    public function dispatch(Event $event): Event
    {
        $eventName = $event->getName() ?: get_class($event);
        if (empty($event->getName())) {
            $event->setName($eventName);
        }

        $this->fired[$eventName] = true;

        $listeners = $this->listeners($eventName);

        foreach ($listeners as $listener) {
            if ($event->isPropagationStopped()) break;
            $listener($event);
        }

        return $event;
    }

    public function emit(string $eventName, array $args = []): void
    {
        $this->fired[$eventName] = true;

        $listeners = $this->listeners($eventName);

        foreach ($listeners as $listener) {
            $listener(...$args);
        }
    }

    /**
     * 内部 filter 实现（接受 variadic 参数）
     */
    public function runFilter(string $eventName, mixed $value, mixed ...$args): mixed
    {
        $this->fired[$eventName] = true;

        $listeners = $this->listeners($eventName);

        foreach ($listeners as $listener) {
            $value = $listener($value, ...$args);
        }

        return $value;
    }

    /**
     * 接口要求的 filter 方法（接受 array 参数）
     */
    public function filter(string $eventName, mixed $value, array $args = []): mixed
    {
        return $this->runFilter($eventName, $value, ...$args);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->on($eventName, [$subscriber, $params]);
            } elseif (is_string($params[0])) {
                $this->on($eventName, [$subscriber, $params[0]], $params[1] ?? 0);
            } elseif (is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->on($eventName, [$subscriber, is_string($listener) ? $listener : $listener[0]], $listener[1] ?? 0);
                }
            }
        }
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
        foreach ($subscriber::getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->off($eventName, [$subscriber, $params]);
            } elseif (is_string($params[0])) {
                $this->off($eventName, [$subscriber, $params[0]]);
            } elseif (is_array($params[0])) {
                foreach ($params as $listener) {
                    $this->off($eventName, [$subscriber, is_string($listener) ? $listener : $listener[0]]);
                }
            }
        }
    }

    public function isFired(string $eventName): bool
    {
        return !empty($this->fired[$eventName]);
    }

    public function clearListeners(string $eventName): void
    {
        $this->off($eventName);
    }

    public function resetAll(): void
    {
        $this->listeners = [];
        $this->wildcardPatterns = [];
        $this->fired = [];
    }

    public function getAllListeners(): array
    {
        return $this->listeners;
    }

    public function getFiredEvents(): array
    {
        return array_keys($this->fired);
    }

    private function compileWildcardPattern(string $pattern): string
    {
        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
        return $regex;
    }

    private function getWildcardListeners(string $eventName): array
    {
        $matched = [];

        foreach ($this->wildcardPatterns as $pattern => $regex) {
            if (preg_match($regex, $eventName)) {
                if (isset($this->listeners[$pattern])) {
                    foreach ($this->listeners[$pattern] as $priority => $entries) {
                        if (!isset($matched[$priority])) {
                            $matched[$priority] = [];
                        }
                        foreach ($entries as $entry) {
                            $callback = is_array($entry) ? $entry['callback'] : $entry;
                            $matched[$priority][] = $callback;
                        }
                    }
                }
            }
        }

        return $matched;
    }

    public static function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        self::getInstance()->on($hook, $callback, $priority, $acceptedArgs);
    }

    public static function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        self::getInstance()->on($hook, $callback, $priority, $acceptedArgs);
    }

    public static function bind(string $hook, callable $callback, int $priority = 10): void
    {
        self::getInstance()->on($hook, $callback, $priority);
    }

    public static function fire(string $hook, mixed ...$args): void
    {
        self::getInstance()->emit($hook, $args);
    }

    public static function applyFilter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return self::getInstance()->filter($hook, $value, $args);
    }

    public static function hasAction(string $hook): bool
    {
        return self::getInstance()->hasListeners($hook);
    }

    public static function hasFilter(string $hook): bool
    {
        return self::getInstance()->hasListeners($hook);
    }

    public static function fired(string $hook): bool
    {
        return self::getInstance()->isFired($hook);
    }

    public static function removeAction(string $hook, ?callable $callback = null, ?int $priority = null): void
    {
        self::getInstance()->off($hook, $callback);
    }

    public static function removeFilter(string $hook, ?callable $callback = null, ?int $priority = null): void
    {
        self::getInstance()->off($hook, $callback);
    }

    public static function clear(string $hook): void
    {
        self::getInstance()->clearListeners($hook);
    }

    public static function reset(): void
    {
        self::getInstance()->resetAll();
    }

    public static function getAllActions(): array
    {
        return self::getInstance()->getAllListeners();
    }

    public static function getAllFilters(): array
    {
        return self::getInstance()->getAllListeners();
    }
}
