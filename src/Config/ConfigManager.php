<?php

declare(strict_types=1);

namespace Framework\Config;

/**
 * 配置管理器
 *
 * 负责加载、缓存、合并和管理应用配置
 *
 * 功能特性：
 * - 配置缓存机制：首次加载后缓存到 storage/cache/config.php
 * - 嵌套环境变量：支持 env('KEY', env('FALLBACK', 'default')) 形式
 * - 动态修改持久化：set() 方法可修改配置并保存到 runtime.php
 * - 配置验证：validate() 方法可验证必需配置项
 */
class ConfigManager
{
    private static ?array $defaults = null;
    private static ?array $cachedConfig = null;
    private static ?array $runtimeConfig = null;
    private static ?string $cachePath = null;
    private static bool $cacheEnabled = true;

    /**
     * 加载配置：先加载框架默认配置，再用用户项目 config/ 覆盖
     */
    public static function load(): array
    {
        if (self::$cachedConfig !== null) {
            return self::$cachedConfig;
        }

        $base = self::resolveBasePath();
        self::$cachePath = $base . '/storage/cache/config.php';

        // 尝试从缓存加载
        if (self::$cacheEnabled && file_exists(self::$cachePath)) {
            $cacheData = require self::$cachePath;
            $cacheTime = filemtime(self::$cachePath);
            $configDir = $base . '/config';

            // 如果配置目录没有更新，使用缓存
            $configModified = is_dir($configDir) ? filemtime($configDir) : 0;
            if ($cacheTime > $configModified) {
                self::$cachedConfig = $cacheData;
                return self::$cachedConfig;
            }
        }

        // 加载运行时动态配置
        self::loadRuntimeConfig($base);

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

        // 合并运行时动态配置
        if (self::$runtimeConfig !== null) {
            foreach (self::$runtimeConfig as $file => $data) {
                if (isset($config[$file]) && is_array($config[$file])) {
                    $config[$file] = self::mergeConfig($config[$file], $data);
                } else {
                    $config[$file] = $data;
                }
            }
        }

        // 替换路径占位符
        $config = self::replacePaths($config, $base);

        // 替换嵌套环境变量
        $config = self::replaceNestedEnv($config);

        // 缓存配置
        self::cacheConfig($config);

        self::$cachedConfig = $config;
        return self::$cachedConfig;
    }

    /**
     * 加载运行时动态配置（持久化修改）
     */
    private static function loadRuntimeConfig(string $base): void
    {
        $runtimeFile = $base . '/storage/config/runtime.php';
        if (file_exists($runtimeFile)) {
            self::$runtimeConfig = require $runtimeFile;
            if (!is_array(self::$runtimeConfig)) {
                self::$runtimeConfig = [];
            }
        } else {
            self::$runtimeConfig = [];
        }
    }

    /**
     * 设置配置值并持久化
     *
     * @param string $key 配置键（支持点号分隔，如 'app.name'）
     * @param mixed $value 配置值
     */
    public static function set(string $key, mixed $value): void
    {
        $base = self::resolveBasePath();
        $runtimeFile = $base . '/storage/config/runtime.php';

        $parts = explode('.', $key);
        $fileName = array_shift($parts);
        $nestedKey = $parts;

        // 加载现有运行时配置
        self::loadRuntimeConfig($base);

        // 设置值
        if (!isset(self::$runtimeConfig[$fileName])) {
            self::$runtimeConfig[$fileName] = [];
        }

        $target = &self::$runtimeConfig[$fileName];
        foreach ($nestedKey as $part) {
            if (!isset($target[$part]) || !is_array($target[$part])) {
                $target[$part] = [];
            }
            $target = &$target[$part];
        }
        $target = $value;
        unset($target);

        // 保存到文件
        $dir = dirname($runtimeFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($runtimeFile, '<?php return ' . var_export(self::$runtimeConfig, true) . ';');

        // 清除内存缓存
        self::$cachedConfig = null;
    }

    /**
     * 获取配置值（支持点号分隔的键）
     *
     * @param string $key 配置键（如 'app.name'）
     * @param mixed $default 默认值
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::load();
        $parts = explode('.', $key);

        $value = $config;
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * 验证必需配置项
     *
     * @param array $rules 验证规则 ['key' => ['required' => true, 'type' => 'string']]
     * @return array 验证失败的配置项
     */
    public static function validate(array $rules): array
    {
        $errors = [];
        $config = self::load();

        foreach ($rules as $key => $rule) {
            $parts = explode('.', $key);
            $value = $config;
            $exists = true;

            foreach ($parts as $part) {
                if (!isset($value[$part])) {
                    $exists = false;
                    break;
                }
                $value = $value[$part];
            }

            if ($exists) {
                // 检查类型
                if (isset($rule['type'])) {
                    $expectedType = $rule['type'];
                    $actualType = gettype($value);

                    if ($expectedType === 'int' && $actualType !== 'integer') {
                        $errors[$key] = "Expected type '{$expectedType}', got '{$actualType}'";
                    } elseif ($expectedType === 'bool' && $actualType !== 'boolean') {
                        $errors[$key] = "Expected type '{$expectedType}', got '{$actualType}'";
                    } elseif ($expectedType === 'string' && $actualType !== 'string') {
                        $errors[$key] = "Expected type '{$expectedType}', got '{$actualType}'";
                    } elseif ($expectedType === 'array' && $actualType !== 'array') {
                        $errors[$key] = "Expected type '{$expectedType}', got '{$actualType}'";
                    }
                }

                // 检查枚举值
                if (isset($rule['in']) && !in_array($value, $rule['in'], true)) {
                    $errors[$key] = "Value must be one of: " . implode(', ', $rule['in']);
                }

                // 检查最小值
                if (isset($rule['min'])) {
                    if (is_numeric($value) && $value < $rule['min']) {
                        $errors[$key] = "Value must be at least {$rule['min']}";
                    } elseif (is_string($value) && strlen($value) < $rule['min']) {
                        $errors[$key] = "Length must be at least {$rule['min']}";
                    }
                }

                // 检查最大值
                if (isset($rule['max'])) {
                    if (is_numeric($value) && $value > $rule['max']) {
                        $errors[$key] = "Value must be at most {$rule['max']}";
                    } elseif (is_string($value) && strlen($value) > $rule['max']) {
                        $errors[$key] = "Length must be at most {$rule['max']}";
                    }
                }
            } elseif ($rule['required'] ?? false) {
                $errors[$key] = "Configuration key is required";
            }
        }

        return $errors;
    }

    /**
     * 禁用配置缓存
     */
    public static function disableCache(): void
    {
        self::$cacheEnabled = false;
        self::$cachedConfig = null;
    }

    /**
     * 清除配置缓存
     */
    public static function clearCache(): void
    {
        self::$cachedConfig = null;
        self::$defaults = null;
        self::$runtimeConfig = null;

        if (self::$cachePath !== null && file_exists(self::$cachePath)) {
            unlink(self::$cachePath);
        }

        $base = self::resolveBasePath();
        $runtimeFile = $base . '/storage/config/runtime.php';
        if (file_exists($runtimeFile)) {
            unlink($runtimeFile);
        }
    }

    /**
     * 缓存配置到文件
     */
    private static function cacheConfig(array $config): void
    {
        if (!self::$cacheEnabled || self::$cachePath === null) {
            return;
        }

        $dir = dirname(self::$cachePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(self::$cachePath, '<?php return ' . var_export($config, true) . ';');
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

        array_walk_recursive($config, function (&$value) use ($base, $storage) {
            if (is_string($value)) {
                $value = str_replace('__BASE_PATH__', $base, $value);
                $value = str_replace('__STORAGE_PATH__', $storage, $value);
            }
        });

        return $config;
    }

    /**
     * 替换嵌套环境变量
     * 支持 env('KEY', env('FALLBACK', 'default')) 和 ${env.KEY} 形式
     */
    private static function replaceNestedEnv(array $config): array
    {
        array_walk_recursive($config, function (&$value) {
            if (is_string($value)) {
                // 匹配 ${env.KEY} 形式
                $value = preg_replace_callback('/\$\{env\.([^}]+)\}/', function ($matches) {
                    $key = $matches[1];
                    return $_ENV[$key] ?? getenv($key) ?: $matches[0];
                }, $value);

                // 匹配 env('KEY', 'default') 形式
                $value = preg_replace_callback('/env\((\w+)(?:,\s*([\'"])([^\'"]*)\2)?\)/', function ($matches) {
                    $key = $matches[1];
                    $default = $matches[3] ?? '';
                    return $_ENV[$key] ?? getenv($key) ?: $default;
                }, $value);
            }
        });

        return $config;
    }

    /**
     * 重置缓存（测试用）
     */
    public static function reset(): void
    {
        self::$defaults = null;
        self::$cachedConfig = null;
        self::$runtimeConfig = null;
    }
}
