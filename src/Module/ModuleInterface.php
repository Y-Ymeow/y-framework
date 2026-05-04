<?php

declare(strict_types=1);

namespace Framework\Module;

interface ModuleInterface
{
    public function getName(): string;

    public function getPath(): string;

    public function getServiceProvider(): ?string;

    public function getConfigFile(): ?string;

    public function getMigrationsPath(): ?string;

    public function getDependencies(): array;

    public function isEnabled(): bool;
}
