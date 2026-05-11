<?php

declare(strict_types=1);

namespace Framework\Events;

class ThemeActivatedEvent extends Event
{
    private string $themeName;

    public function __construct(string $themeName)
    {
        parent::__construct('theme.activated');
        $this->themeName = $themeName;
    }

    public function getThemeName(): string
    {
        return $this->themeName;
    }
}