<?php

declare(strict_types=1);

namespace Framework\Theme;

use Admin\Theme\ThemeManager as AdminThemeManager;

class ThemeManager
{
    protected string $themesPath;

    protected array $instances = [];

    protected ?string $activeThemeName = null;

    protected ?ThemeInterface $activeThemeInstance = null;

    public function __construct(string $themesPath)
    {
        $this->themesPath = $themesPath;
    }

    public function scan(): array
    {
        if (!is_dir($this->themesPath)) {
            return [];
        }

        $themes = [];
        foreach (glob($this->themesPath . '/*/theme.json') as $jsonFile) {
            $themeDir = basename(dirname($jsonFile));
            $config = json_decode(file_get_contents($jsonFile), true);
            if ($config) {
                $config['directory'] = $themeDir;
                $themes[$themeDir] = $config;
            }
        }

        return $themes;
    }

    public function getActiveThemeName(): string
    {
        if ($this->activeThemeName !== null) {
            return $this->activeThemeName;
        }
        return $this->activeThemeName = AdminThemeManager::getActiveTheme();
    }

    public function setActiveTheme(string $theme): void
    {
        AdminThemeManager::setActiveTheme($theme);
        $this->activeThemeName = $theme;
        $this->activeThemeInstance = null;
    }

    public function getActiveThemeObject(): ?ThemeInterface
    {
        $name = $this->getActiveThemeName();
        if ($name === '') {
            return null;
        }

        if ($this->activeThemeInstance !== null) {
            return $this->activeThemeInstance;
        }

        return $this->activeThemeInstance = $this->instantiate($name);
    }

    public function instantiate(string $name): ?ThemeInterface
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $themeDir = $this->themesPath . '/' . $name;

        if (!is_dir($themeDir)) {
            return null;
        }

        $instance = $this->createInstance($name, $themeDir);

        if ($instance !== null) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    public function getAllThemeObjects(): array
    {
        $themes = $this->scan();
        $objects = [];

        foreach ($themes as $name => $config) {
            $obj = $this->instantiate($name);
            if ($obj !== null) {
                $objects[$name] = $obj;
            }
        }

        return $objects;
    }

    protected function createInstance(string $name, string $themeDir): ?ThemeInterface
    {
        $themeClassFile = $themeDir . '/Theme.php';

        if (file_exists($themeClassFile)) {
            $className = '\\Theme\\' . $this->sanitizeClassName($name) . '\\Theme';

            if (!class_exists($className, false)) {
                require_once $themeClassFile;
            }

            if (class_exists($className)) {
                $ref = new \ReflectionClass($className);
                if ($ref->isSubclassOf(ThemeInterface::class)) {
                    return $ref->newInstance($name, $themeDir);
                }
            }
        }

        return new class($name, $themeDir) extends Theme {
            public function boot(): void {}
        };
    }

    protected function sanitizeClassName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', ucfirst($name));
    }
}