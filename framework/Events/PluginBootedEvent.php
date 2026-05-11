<?php

declare(strict_types=1);

namespace Framework\Events;

use Framework\Plugin\PluginInterface;

class PluginBootedEvent extends Event
{
    private PluginInterface $plugin;

    public function __construct(PluginInterface $plugin)
    {
        parent::__construct('plugin.booted');
        $this->plugin = $plugin;
    }

    public function getPlugin(): PluginInterface
    {
        return $this->plugin;
    }
}