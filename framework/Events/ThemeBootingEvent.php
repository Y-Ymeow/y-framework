<?php

declare(strict_types=1);

namespace Framework\Events;

use Framework\Theme\ThemeInterface;

class ThemeBootingEvent extends Event
{
    private ThemeInterface $theme;

    public function __construct(ThemeInterface $theme)
    {
        parent::__construct('theme.booting');
        $this->theme = $theme;
    }

    public function getTheme(): ThemeInterface
    {
        return $this->theme;
    }
}