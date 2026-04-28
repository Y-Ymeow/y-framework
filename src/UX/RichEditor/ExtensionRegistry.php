<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

class ExtensionRegistry
{
    private static array $extensions = [];
    private static array $parsers = [];
    private static array $formatters = [];

    public static function register(string $name, RichEditorExtension $extension): void
    {
        self::$extensions[$name] = $extension;
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

    public static function clear(): void
    {
        self::$extensions = [];
        self::$parsers = [];
        self::$formatters = [];
    }
}
