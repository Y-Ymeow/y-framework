<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

/**
 * 扩展注册表
 *
 * 全局注册和管理富文本编辑器扩展、解析器、格式化器。
 *
 * @ux-category RichEditor
 * @ux-since 1.0.0
 * @ux-example
 * ExtensionRegistry::register('emoji', new EmojiExtension());
 * ExtensionRegistry::registerParser('markdown', fn($c) => Markdown::parse($c));
 * @ux-example-end
 */
class ExtensionRegistry
{
    private static array $extensions = [];
    private static array $parsers = [];
    private static array $formatters = [];

    /**
     * 注册扩展
     * @param string $name 扩展名称
     * @param RichEditorExtension $extension 扩展实例
     */
    public static function register(string $name, RichEditorExtension $extension): void
    {
        self::$extensions[$name] = $extension;
    }

    /**
     * 获取扩展
     * @param string $name 扩展名称
     * @return RichEditorExtension|null
     */
    public static function get(string $name): ?RichEditorExtension
    {
        return self::$extensions[$name] ?? null;
    }

    /**
     * 检查扩展是否存在
     * @param string $name 扩展名称
     * @return bool
     */
    public static function has(string $name): bool
    {
        return isset(self::$extensions[$name]);
    }

    /**
     * 获取所有注册扩展
     * @return array
     */
    public static function all(): array
    {
        return self::$extensions;
    }

    /**
     * 移除扩展
     * @param string $name 扩展名称
     */
    public static function remove(string $name): void
    {
        unset(self::$extensions[$name]);
    }

    /**
     * 注册解析器
     * @param string $name 解析器名称
     * @param callable $parser 解析器回调
     */
    public static function registerParser(string $name, callable $parser): void
    {
        self::$parsers[$name] = $parser;
    }

    /**
     * 获取解析器
     * @param string $name 解析器名称
     * @return callable|null
     */
    public static function getParser(string $name): ?callable
    {
        return self::$parsers[$name] ?? null;
    }

    /**
     * 注册格式化器
     * @param string $format 格式名称
     * @param callable $formatter 格式化器回调
     */
    public static function registerFormatter(string $format, callable $formatter): void
    {
        self::$formatters[$format] = $formatter;
    }

    /**
     * 获取格式化器
     * @param string $format 格式名称
     * @return callable|null
     */
    public static function getFormatter(string $format): ?callable
    {
        return self::$formatters[$format] ?? null;
    }

    /**
     * 使用解析器或扩展解析内容
     * @param string $content 内容
     * @param string $parserName 解析器名称
     * @return string 解析后的内容
     */
    public static function parseWith(string $content, string $parserName): string
    {
        $parser = self::getParser($parserName);
        if ($parser) {
            return $parser($content);
        }

        foreach (self::$extensions as $extension) {
            $content = $extension->parse($content);
        }

        return $content;
    }

    /**
     * 使用格式化器格式化内容
     * @param string $content 内容
     * @param string $format 格式名称
     * @return string 格式化后的内容
     */
    public static function formatAs(string $content, string $format): string
    {
        $formatter = self::getFormatter($format);
        if ($formatter) {
            return $formatter($content);
        }

        return $content;
    }

    /**
     * 清空所有注册
     */
    public static function clear(): void
    {
        self::$extensions = [];
        self::$parsers = [];
        self::$formatters = [];
    }
}
