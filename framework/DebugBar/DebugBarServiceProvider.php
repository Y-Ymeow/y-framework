<?php

declare(strict_types=1);

namespace Framework\DebugBar;

use Framework\Foundation\ServiceProvider;

class DebugBarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $debug = \Framework\Foundation\Application::isDebug();
        if (!$debug) {
            return;
        }

        $debugBar = DebugBar::getInstance();
        $this->app->instance(DebugBar::class, $debugBar);

        $listener = new DebugBarListener($debugBar);
        $listener->register();
    }
}