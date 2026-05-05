<?php

declare(strict_types=1);

namespace Framework\Foundation;

abstract class ServiceProvider
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(): void {}

    public function boot(): void {}
}
