<?php

declare(strict_types=1);

namespace Framework\Theme;

use Admin\Theme\ThemeManager as AdminThemeManager;
use Framework\View\Base\Element;

abstract class Theme implements ThemeInterface
{
    protected string $name;

    protected string $path;

    protected array $config = [];

    protected array $settings = [];

    public function __construct(string $name, string $path)
    {
        $this->name = $name;
        $this->path = $path;
        $this->config = $this->loadConfig();
        $this->settings = AdminThemeManager::getThemeSettings($name);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? $default;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getStyles(): array
    {
        return [];
    }

    public function getScripts(): array
    {
        return [];
    }

    public function getNavLocations(): array
    {
        return [];
    }

    public function getWidgetAreas(): array
    {
        return [];
    }

    public function renderHeader(): Element
    {
        return Element::make('header');
    }

    public function renderFooter(): Element
    {
        return Element::make('footer');
    }

    public function asset(string $path): string
    {
        $base = '/themes/' . $this->name;
        return $base . '/assets/' . ltrim($path, '/');
    }

    public function renderCssVariables(): string
    {
        $settingsDef = $this->getConfig('settings', []);
        if (empty($settingsDef)) {
            return '';
        }

        $vars = '';
        foreach ($settingsDef as $key => $def) {
            $value = $this->getSetting($key, $def['default'] ?? null);
            if ($value === null) {
                continue;
            }
            $cssVar = '--theme-' . str_replace('_', '-', $key);
            $vars .= "  {$cssVar}: {$value};\n";
        }

        return $vars;
    }

    protected function loadConfig(): array
    {
        $jsonFile = $this->path . '/theme.json';
        if (!file_exists($jsonFile)) {
            return [];
        }
        return json_decode(file_get_contents($jsonFile), true) ?: [];
    }
}