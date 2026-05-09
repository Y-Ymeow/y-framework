<?php

declare(strict_types=1);

namespace Framework\Plugin;

interface PluginInterface
{
    public function getName(): string;

    public function getTitle(): string;

    public function getDescription(): string;

    public function getVersion(): string;

    public function boot(): void;
}