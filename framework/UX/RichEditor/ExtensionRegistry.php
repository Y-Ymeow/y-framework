<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

/**
 * 扩展注册表
 *
 * v2.0: 同时管理旧版扩展和 Block 类型。
 * 当扩展调用 asBlock() 时，自动同步注册到 BlockRegistry。
 *
 * @ux-category RichEditor
 * @ux-since 1.0.0
 * @ux-example
 * ExtensionRegistry::register('emoji', new EmojiExtension());
 * ExtensionRegistry::register('emoji', (new EmojiExtension())->asBlock());
 */
class ExtensionRegistry
{
    private static array $extensions = [];
    private static array $parsers = [];
    private static array $formatters = [];

    public static function register(string $name, RichEditorExtension $extension): void
    {
        self::$extensions[$name] = $extension;

        if ($extension->isBlockMode()) {
            BlockRegistry::register($name, $extension->toBlockType());
        }
    }

    public static function get(string $name): ?RichEditorExtension
    {
        return self::$extensions[$name] ?? null;
    }

    public static function has(string $name): bool
    {
        return isset(self::$extensions[$name]);
    }

    public static function all(): array
    {
        return self::$extensions;
    }

    public static function remove(string $name): void
    {
        unset(self::$extensions[$name]);
    }

    public static function registerParser(string $name, callable $parser): void
    {
        self::$parsers[$name] = $parser;
    }

    public static function getParser(string $name): ?callable
    {
        return self::$parsers[$name] ?? null;
    }

    public static function registerFormatter(string $format, callable $formatter): void
    {
        self::$formatters[$format] = $formatter;
    }

    public static function getFormatter(string $format): ?callable
    {
        return self::$formatters[$format] ?? null;
    }

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

    public static function formatAs(string $content, string $format): string
    {
        $formatter = self::getFormatter($format);
        if ($formatter) {
            return $formatter($content);
        }

        return $content;
    }

    /**
     * 获取所有扩展的序列化定义（传给前端）
     */
    public static function allDefinitions(): array
    {
        $definitions = [];
        foreach (self::$extensions as $name => $extension) {
            $definitions[$name] = $extension->toArray();
        }
        return $definitions;
    }

    /**
     * 获取 Block 模式的扩展列表
     */
    public static function getBlockExtensions(): array
    {
        return array_filter(self::$extensions, fn(RichEditorExtension $ext) => $ext->isBlockMode());
    }

    /**
     * 获取非 Block 模式的扩展列表（传统行内扩展）
     */
    public static function getInlineExtensions(): array
    {
        return array_filter(self::$extensions, fn(RichEditorExtension $ext) => !$ext->isBlockMode());
    }

    public static function clear(): void
    {
        self::$extensions = [];
        self::$parsers = [];
        self::$formatters = [];
    }
}
