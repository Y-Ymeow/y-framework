<?php

declare(strict_types=1);

namespace Framework\Component;

use Framework\Component\Attribute\LiveListener;

class LiveEventBus
{
    private static array $componentStates = [];
    private static array $emittedEvents = [];

    public static function storeComponentState(string $componentId, string $class, string $state): void
    {
        self::$componentStates[$componentId] = [
            'class' => $class,
            'state' => $state,
        ];
    }

    public static function getComponentState(string $componentId): ?array
    {
        return self::$componentStates[$componentId] ?? null;
    }

    public static function getAllComponentStates(): array
    {
        return self::$componentStates;
    }

    public static function recordEmittedEvent(string $event, mixed $data): void
    {
        self::$emittedEvents[] = [
            'event' => $event,
            'data' => $data,
        ];
    }

    public static function getEmittedEvents(): array
    {
        $events = self::$emittedEvents;
        self::$emittedEvents = [];
        return $events;
    }

    public static function reset(): void
    {
        self::$componentStates = [];
        self::$emittedEvents = [];
    }

    public static function findListenersForEvent(string $event, string $excludeComponentId = ''): array
    {
        $listeners = [];
        
        foreach (self::$componentStates as $componentId => $stateInfo) {
            if ($componentId === $excludeComponentId) continue;
            
            $class = $stateInfo['class'];
            if (!class_exists($class)) continue;
            
            $ref = new \ReflectionClass($class);
            foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $attrs = $method->getAttributes(LiveListener::class);
                foreach ($attrs as $attr) {
                    $listener = $attr->newInstance();
                    if ($listener->event === $event) {
                        $listeners[] = [
                            'componentId' => $componentId,
                            'class' => $class,
                            'handler' => $method->getName(),
                            'state' => $stateInfo['state'],
                        ];
                    }
                }
            }
        }
        
        return $listeners;
    }
}
