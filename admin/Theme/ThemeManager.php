<?php

declare(strict_types=1);

namespace Admin\Theme;

class ThemeManager
{
    protected static ?string $activeTheme = null;

    public static function getThemesPath(): string
    {
        return base_path('/themes');
    }

    public static function getAvailableThemes(): array
    {
        $path = static::getThemesPath();
        if (!is_dir($path)) {
            return [];
        }

        $themes = [];
        foreach (glob($path . '/*/theme.json') as $jsonFile) {
            $themeDir = basename(dirname($jsonFile));
            $config = json_decode(file_get_contents($jsonFile), true);
            if ($config) {
                $config['directory'] = $themeDir;
                $themes[$themeDir] = $config;
            }
        }

        return $themes;
    }

    public static function getActiveTheme(): string
    {
        if (static::$activeTheme !== null) {
            return static::$activeTheme;
        }

        $row = db()->table('theme_settings')
            ->where('key', 'active_theme')
            ->first();

        return static::$activeTheme = $row ? ($row['value'] ?? 'default') : 'default';
    }

    public static function setActiveTheme(string $theme): void
    {
        $exists = db()->table('theme_settings')
            ->where('key', 'active_theme')
            ->first();

        if ($exists) {
            db()->table('theme_settings')
                ->where('key', 'active_theme')
                ->update(['value' => $theme]);
        } else {
            db()->table('theme_settings')
                ->insert(['theme' => $theme, 'key' => 'active_theme', 'value' => $theme]);
        }

        static::$activeTheme = $theme;
    }

    public static function getThemeConfig(string $theme): ?array
    {
        $jsonFile = static::getThemesPath() . '/' . $theme . '/theme.json';
        if (!file_exists($jsonFile)) {
            return null;
        }
        return json_decode(file_get_contents($jsonFile), true);
    }

    public static function getThemeSettings(string $theme): array
    {
        $results = db()->table('theme_settings')
            ->where('theme', $theme)
            ->get()
            ->toArray();

        $settings = [];
        foreach ($results as $row) {
            if ($row['key'] !== 'active_theme') {
                $settings[$row['key']] = $row['value'];
            }
        }

        return $settings;
    }

    public static function saveThemeSettings(string $theme, array $settings): void
    {
        foreach ($settings as $key => $value) {
            $exists = db()->table('theme_settings')
                ->where('theme', $theme)
                ->where('key', $key)
                ->first();

            if ($exists) {
                db()->table('theme_settings')
                    ->where('theme', $theme)
                    ->where('key', $key)
                    ->update(['value' => $value]);
            } else {
                db()->table('theme_settings')
                    ->insert(['theme' => $theme, 'key' => $key, 'value' => $value]);
            }
        }
    }
}
