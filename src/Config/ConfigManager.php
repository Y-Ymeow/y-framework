<?php

declare(strict_types=1);

namespace Framework\Config;

class ConfigManager
{
    private static ?array $defaults = null;

    /**
     * 加载配置：先加载框架默认配置，再用用户项目 config/ 覆盖
     */
    public static function load(): array
    {
        if (self::$defaults === null) {
            $defaultsDir = dirname(__DIR__) . '/Config/defaults';
            self::$defaults = [];
            if (is_dir($defaultsDir)) {
                foreach (glob($defaultsDir . '/*.php') as $file) {
                    $name = basename($file, '.php');
                    self::$defaults[$name] = require $file;
                }
            }
        }

        $base = self::resolveBasePath();
        $config = self::$defaults;

        // 合并用户项目 config/ 覆盖
        $userConfigDir = $base . '/config';
        if (is_dir($userConfigDir)) {
            foreach (glob($userConfigDir . '/*.php') as $file) {
                $name = basename($file, '.php');
                $user = require $file;
                if (is_array($user)) {
                    if (isset($config[$name]) && is_array($config[$name])) {
                        $config[$name] = self::mergeConfig($config[$name], $user);
                    } else {
                        $config[$name] = $user;
                    }
                }
            }
        }

        // 替换路径占位符
        $config = self::replacePaths($config, $base);
        self::replaceEnv($config);

        return $config;
    }

    /**
     * 智能合并配置：关联数组递归合并，索引数组完全替换
     */
    private static function mergeConfig(array $default, array $user): array
    {
        $result = $default;

        foreach ($user as $key => $value) {
            if (is_int($key)) {
                // 索引数组：完全替换
                $result[$key] = $value;
            } elseif (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                // 关联数组：递归合并
                $result[$key] = self::mergeConfig($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }

        // 清理多余的数字键（当用户数组比默认数组短时）
        if (array_is_list($default) && array_is_list($user)) {
            $result = array_slice($result, 0, count($user), true);
        }

        return $result;
    }

    private static function resolveBasePath(): string
    {
        if (isset($GLOBALS['app']) && $GLOBALS['app'] instanceof \Framework\Foundation\Application) {
            return $GLOBALS['app']->basePath();
        }
        return dirname(__DIR__, 2);
    }

    private static function replacePaths(array $config, string $base): array
    {
        $storage = $base . '/storage';
        // 获取环境变量中的 APP_URL 来计算默认 URL
        $envUrl = $_ENV['APP_URL'] ?? '';

        array_walk_recursive($config, function (&$value) use ($base, $storage, $envUrl) {
            if (is_string($value)) {
                $value = str_replace('__BASE_PATH__', $base, $value);
                $value = str_replace('__STORAGE_PATH__', $storage, $value);
            }
        });

        return $config;
    }

    /**
     * 遍历配置数组，用 env() 替换所有 env('KEY', default) 引用。
     * 因为框架默认配置在 vendor/ 中，无法在编译时调用 helper，
     * 所以配置中写的是原始值，这里做运行时替换。
     * 用户 config/ 里的仍然直接使用 env() 和 base_path() 等 helper，
     * 因为用户 config/ 在项目根目录，可以直接调用。
     */
    private static function replaceEnv(array &$config): void
    {
        // 用户 config 在 require 时已经通过 env() / base_path() 实时求值，
        // 框架默认配置使用了 __BASE_PATH__ 和 __STORAGE_PATH__ 占位符，
        // 已经在上面的 replacePaths 中处理完毕。
        // env 值在框架默认配置中使用的是硬编码的默认值（如 'file', 'en' 等），
        // 用户在项目 .env 或 config/ 中可覆盖，所以这里不需要再对框架默认配置做 env 替换。
    }

    /**
     * 重置缓存（测试用）
     */
    public static function reset(): void
    {
        self::$defaults = null;
    }
}