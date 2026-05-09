<?php

declare(strict_types=1);

namespace Framework\Plugin;

abstract class Plugin implements PluginInterface
{
    protected string $name;

    protected string $title;

    protected string $description;

    protected string $version;

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}