<?php

declare(strict_types=1);

namespace Framework\Module;

abstract class BaseModule implements ModuleInterface
{
    protected string $name;
    protected string $path;
    protected ?string $serviceProvider = null;
    protected ?string $configFile = null;
    protected ?string $migrationsPath = null;
    protected array $dependencies = [];
    protected bool $enabled = true;

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getServiceProvider(): ?string
    {
        return $this->serviceProvider;
    }

    public function getConfigFile(): ?string
    {
        return $this->configFile;
    }

    public function getMigrationsPath(): ?string
    {
        return $this->migrationsPath;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }
}
