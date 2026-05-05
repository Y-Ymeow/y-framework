<?php

declare(strict_types=1);

namespace Framework\Events;

class CollectorRegisteredEvent extends Event
{
    private string $name;
    private object $collector;

    public function __construct(string $name, object $collector)
    {
        parent::__construct('collector.registered');
        $this->name = $name;
        $this->collector = $collector;
    }

    public function getCollectorName(): string
    {
        return $this->name;
    }

    public function getCollector(): object
    {
        return $this->collector;
    }
}