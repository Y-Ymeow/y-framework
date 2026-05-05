<?php

declare(strict_types=1);

namespace Framework\Module;

use Framework\Foundation\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected ModuleInterface $module;

    public function setModule(ModuleInterface $module): void
    {
        $this->module = $module;
    }

    public function getModule(): ModuleInterface
    {
        return $this->module;
    }

    public function modulePath(string $path = ''): string
    {
        return $this->module->getPath() . ($path ? '/' . ltrim($path, '/') : '');
    }
}
