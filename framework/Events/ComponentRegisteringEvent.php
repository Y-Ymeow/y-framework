<?php

declare(strict_types=1);

namespace Framework\Events;

class ComponentRegisteringEvent extends Event
{
    private array $component;

    public function __construct(array $component)
    {
        parent::__construct('components.registering');
        $this->component = $component;
    }

    public function getComponent(): array
    {
        return $this->component;
    }
}