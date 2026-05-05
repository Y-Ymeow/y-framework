<?php

declare(strict_types=1);

namespace Framework\Events;

class ComponentRegisteredEvent extends Event
{
    private array $component;

    public function __construct(array $component)
    {
        parent::__construct('components.registered');
        $this->component = $component;
    }

    public function getComponent(): array
    {
        return $this->component;
    }
}