<?php

declare(strict_types=1);

namespace Framework\Events;

class ServiceRegisteringEvent extends Event
{
    private string $name;
    private string $class;
    private bool $singleton;

    public function __construct(string $name, string $class, bool $singleton)
    {
        parent::__construct('services.registering');
        $this->name = $name;
        $this->class = $class;
        $this->singleton = $singleton;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function isSingleton(): bool
    {
        return $this->singleton;
    }
}