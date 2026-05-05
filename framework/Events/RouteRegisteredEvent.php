<?php

declare(strict_types=1);

namespace Framework\Events;

class RouteRegisteredEvent extends Event
{
    private array $route;

    public function __construct(array $route)
    {
        parent::__construct('routes.registered');
        $this->route = $route;
    }

    public function getRoute(): array
    {
        return $this->route;
    }
}