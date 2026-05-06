<?php

declare(strict_types=1);

namespace Framework\Intl;

use Framework\Support\Arr;

class Translator
{
    private static array $translations = [];
    private static string $locale = 'en';
    private static string $fallbackLocale = 'en';
    private static string $basePath = '';

    public static function init(string $basePath, string $locale = 'en', string $fallback = 'en'): void
    {
        self::$basePath = $basePath;
        self::$locale = $locale;
        self::$fallbackLocale = $fallback;
        self::load($locale);
        self::load(self::$fallbackLocale);
    }

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
        self::load($locale);
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function setFallbackLocale(string $locale): void
    {
        self::$fallbackLocale = $locale;
        self::load($locale);
    }

    public static function load(string $locale): void
    {
        $dir = self::$basePath . "/{$locale}";

        if (!is_dir($dir)) return;

        // 加载根目录下的翻译文件（如 messages.php, pagination.php）
        foreach (glob("{$dir}/*.php") as $file) {
            $key = basename($file, '.php');
            $messages = require $file;
            if (is_array($messages)) {
                if (!isset(self::$translations[$locale])) {
                    self::$translations[$locale] = [];
                }
                self::$translations[$locale][$key] = $messages;
            }
        }

        // 加载子目录（命名空间）下的翻译文件（如 ux/datatable.php）
        foreach (glob("{$dir}/*", GLOB_ONLYDIR) as $subdir) {
            $namespace = basename($subdir);
            foreach (glob("{$subdir}/*.php") as $file) {
                $key = basename($file, '.php');
                $messages = require $file;
                if (is_array($messages)) {
                    if (!isset(self::$translations[$locale][$namespace])) {
                        self::$translations[$locale][$namespace] = [];
                    }
                    self::$translations[$locale][$namespace][$key] = $messages;
                }
            }
        }
    }

    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::$locale;
        $translation = self::findTranslation($key, $locale);

        if ($translation === null && $locale !== self::$fallbackLocale) {
            $translation = self::findTranslation($key, self::$fallbackLocale);
        }

        if ($translation === null) {
            return $key;
        }

        return self::replaceParameters($translation, $replace);
    }

    private static function findTranslation(string $key, string $locale): ?string
    {
        // 检查是否使用命名空间格式：namespace:key（如 ux:datatable.empty_data）
        if (str_contains($key, ':')) {
            [$namespace, $remainder] = explode(':', $key, 2);
            
            // 命名空间不存在，返回 null
            if (!isset(self::$translations[$locale][$namespace])) {
                return null;
            }
            
            // 解析文件名和嵌套键（如 datatable.empty_data → file=datatable, key=empty_data）
            $parts = explode('.', $remainder);
            $file = array_shift($parts);
            $nestedKey = implode('.', $parts);
            
            if (!isset(self::$translations[$locale][$namespace][$file])) {
                return null;
            }
            
            if (!is_array(self::$translations[$locale][$namespace][$file])) {
                return self::$translations[$locale][$namespace][$file] ?? null;
            }


            $message = Arr::get(self::$translations[$locale][$namespace][$file], $nestedKey);
            return is_string($message) ? $message : null;
        }
        
        // 默认格式：file.key（如 messages.welcome 或 pagination.showing）
        $parts = explode('.', $key);
        $file = array_shift($parts);
        $nestedKey = implode('.', $parts);

        if (!isset(self::$translations[$locale][$file])) {
            return null;
        }

        $message = Arr::get(self::$translations[$locale][$file], $nestedKey);
        return is_string($message) ? $message : null;
    }

    private static function replaceParameters(string $message, array $replace): string
    {
        // 按参数名长度从长到短排序，避免前缀冲突
        uksort($replace, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($replace as $key => $value) {
            $value = (string) $value;
            // 支持 :key 格式（如 :from, :total）
            $message = preg_replace('/:' . preg_quote($key, '/') . '(?!\w)/', $value, $message);
            // 支持 {key} 格式（如 {from}, {total}）
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        return $message;
    }

    public static function choice(string $key, int|float|array $number, array $replace = [], ?string $locale = null): string
    {
        $number = is_array($number) ? ($number['number'] ?? 1) : $number;
        $translation = self::get($key, $replace, $locale);

        $segments = explode('|', $translation);
        $count = count($segments);

        if ($count === 1) {
            return $translation;
        }

        if ($count === 2) {
            return $number === 1 ? $segments[0] : $segments[1];
        }

        if ($count === 3) {
            return match (true) {
                $number === 0 => $segments[0],
                $number === 1 => $segments[1],
                default => $segments[2],
            };
        }

        foreach ($segments as $segment) {
            if (preg_match('/^\{(\d+)(,(\d+))?\}\s*(.*)$/', $segment, $matches)) {
                $min = (int) $matches[1];
                $max = isset($matches[3]) ? (int) $matches[3] : null;
                if ($max === null && $number === $min) {
                    return $matches[4];
                }
                if ($max !== null && $number >= $min && $number <= $max) {
                    return $matches[4];
                }
            }
        }

        return end($segments);
    }

    public static function has(string $key, ?string $locale = null): bool
    {
        return self::findTranslation($key, $locale ?? self::$locale) !== null;
    }

    public static function all(?string $locale = null): array
    {
        return self::$translations[$locale ?? self::$locale] ?? [];
    }

    /**
     * 批量获取翻译
     */
    public static function getMany(array $keys, ?string $locale = null): array
    {
        $locale = $locale ?? self::$locale;
        $result = [];
        foreach ($keys as $key) {
            if (is_string($key)) {
                $result[$key] = self::get($key, [], $locale);   
                continue;             
            }

            if (is_array($key)) {
                $data = $key;
                $key = $data['key'] . ' ' . ($data['params'] ?? '');
                $result[$key] = self::get($data['key'], json_decode($data['params'] ?? '{}', true), $locale);
            }
        }
        return $result;
    }
}
