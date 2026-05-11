<?php

declare(strict_types=1);

namespace Framework\Events;

class ThemeSettingsSavedEvent extends Event
{
    private string $themeName;
    private array $settings;

    public function __construct(string $themeName, array $settings)
    {
        parent::__construct('theme.settings_saved');
        $this->themeName = $themeName;
        $this->settings = $settings;
    }

    public function getThemeName(): string
    {
        return $this->themeName;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}