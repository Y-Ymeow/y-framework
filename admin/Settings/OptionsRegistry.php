<?php

namespace Admin\Settings;

class OptionsRegistry
{
    protected static array $options = [];
    protected static array $optionDefinitions = [];
    protected static bool $booted = false;

    public static function register(string $key, array $definition): void
    {
        static::$optionDefinitions[$key] = $definition;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!static::$booted) {
            static::boot();
        }

        if (isset(static::$options[$key])) {
            return static::$options[$key];
        }

        $def = static::$optionDefinitions[$key] ?? null;
        if ($def && isset($def['default'])) {
            return $def['default'];
        }

        return $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::$options[$key] = $value;
        static::persist();
    }

    public static function getAll(): array
    {
        if (!static::$booted) {
            static::boot();
        }

        return static::$options;
    }

    public static function getDefinition(string $key): ?array
    {
        return static::$optionDefinitions[$key] ?? null;
    }

    public static function getAllDefinitions(): array
    {
        if (!static::$booted) {
            static::boot();
        }

        return static::$optionDefinitions;
    }

    public static function getGroups(): array
    {
        if (!static::$booted) {
            static::boot();
        }

        $groups = [];
        foreach (static::$optionDefinitions as $key => $def) {
            $group = $def['group'] ?? t('admin.settings.general');
            if (!in_array($group, $groups, true)) {
                $groups[] = $group;
            }
        }
        return $groups;
    }

    public static function getDefinitionsByGroup(string $group): array
    {
        if (!static::$booted) {
            static::boot();
        }

        $result = [];
        foreach (static::$optionDefinitions as $key => $def) {
            if (($def['group'] ?? t('admin.settings.general')) === $group) {
                $result[$key] = $def;
            }
        }
        return $result;
    }

    public static function update(array $data): void
    {
        foreach ($data as $key => $value) {
            if (isset(static::$optionDefinitions[$key])) {
                $def = static::$optionDefinitions[$key];
                if (($def['type'] ?? 'text') === 'switch') {
                    static::$options[$key] = (bool)$value;
                } else {
                    static::$options[$key] = $value;
                }
            }
        }
        static::persist();
    }

    public static function delete(string $key): void
    {
        unset(static::$options[$key]);
        static::persist();
    }

    public static function exists(string $key): bool
    {
        if (!static::$booted) {
            static::boot();
        }

        return isset(static::$options[$key]);
    }

    protected static function boot(): void
    {
        $storagePath = base_path('/storage/options.php');

        if (file_exists($storagePath)) {
            static::$options = (array) include $storagePath;
        }

        static::loadOptionDefinitions();

        foreach (static::$optionDefinitions as $key => $def) {
            if (!isset(static::$options[$key]) && isset($def['default'])) {
                static::$options[$key] = $def['default'];
            }
        }

        static::$booted = true;
    }

    protected static function loadOptionDefinitions(): void
    {
        $dir = base_path('/admin/Settings');
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*.php') as $file) {
            if (basename($file) === 'OptionsRegistry.php') {
                continue;
            }
            $class = '\\Admin\\Settings\\' . basename($file, '.php');
            if (class_exists($class) && method_exists($class, 'registerOptions')) {
                $class::registerOptions();
            }
        }
    }

    protected static function persist(): void
    {
        $storagePath = base_path('/storage/options.php');
        $dir = dirname($storagePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = '<?php return ' . var_export(static::$options, true) . ';';
        file_put_contents($storagePath, $data);
    }
}
