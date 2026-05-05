<?php

declare(strict_types=1);

namespace Framework\Events;

interface EventDispatcherInterface
{
    public function on(string $eventName, callable $listener, int $priority = 0): void;

    public function off(string $eventName, ?callable $listener = null): void;

    public function listeners(string $eventName): array;

    public function hasListeners(string $eventName): bool;

    public function dispatch(Event $event): Event;

    public function emit(string $eventName, array $args = []): void;

    public function filter(string $eventName, mixed $value, array $args = []): mixed;

    public function addSubscriber(EventSubscriberInterface $subscriber): void;

    public function removeSubscriber(EventSubscriberInterface $subscriber): void;
}
