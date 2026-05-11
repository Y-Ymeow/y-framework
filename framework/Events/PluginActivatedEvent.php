<?php

declare(strict_types=1);

namespace Framework\Events;

class PluginActivatedEvent extends Event
{
    private string $pluginName;

    public function __construct(string $pluginName)
    {
        parent::__construct('plugin.activated');
        $this->pluginName = $pluginName;
    }

    public function getPluginName(): string
    {
        return $this->pluginName;
    }
}