<?php

declare(strict_types=1);

namespace Framework\DebugBar;

use Framework\Events\Hook;
use Framework\Events\ResponseCreatedEvent;
use Framework\Foundation\Application;

class DebugBarListener
{
    private DebugBar $debugBar;

    public function __construct(DebugBar $debugBar)
    {
        $this->debugBar = $debugBar;
    }

    public function register(): void
    {
        Hook::getInstance()->on('response.created', [$this, 'onResponseCreated'], 10);
    }

    public function onResponseCreated(ResponseCreatedEvent $event): void
    {
        if (!Application::isDebug()) return;

        SqlCollector::register();
        RouteCollector::register();
        RequestCollector::register();
        \Framework\DebugBar\Collectors\SessionCollector::register();

        $this->debugBar->collect();
    }
}