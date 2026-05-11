<?php

declare(strict_types=1);

namespace Framework\Events;

use Framework\Theme\ThemeInterface;
use Framework\View\Base\Element;

class ThemeBootedEvent extends Event
{
    private ThemeInterface $theme;
    private Element $wrapper;

    public function __construct(ThemeInterface $theme, Element $wrapper)
    {
        parent::__construct('theme.booted');
        $this->theme = $theme;
        $this->wrapper = $wrapper;
    }

    public function getTheme(): ThemeInterface
    {
        return $this->theme;
    }

    public function getWrapper(): Element
    {
        return $this->wrapper;
    }
}