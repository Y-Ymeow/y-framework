<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

use Framework\View\Base\Element;

class InlineFormatRegistry
{
    private static array $formats = [];

    public static function register(string $name, InlineFormat $format): void
    {
        self::$formats[$name] = $format;
    }

    public static function get(string $name): ?InlineFormat
    {
        return self::$formats[$name] ?? null;
    }

    public static function has(string $name): bool
    {
        return isset(self::$formats[$name]);
    }

    public static function all(): array
    {
        return self::$formats;
    }

    public static function allDefinitions(): array
    {
        $definitions = [];
        foreach (self::$formats as $name => $format) {
            $definitions[$name] = $format->toArray();
        }
        return $definitions;
    }

    public static function remove(string $name): void
    {
        unset(self::$formats[$name]);
    }

    public static function clear(): void
    {
        self::$formats = [];
    }

    public static function registerCoreFormats(): void
    {
        self::register('bold', InlineFormat::make('bold')
            ->title(t('editor.format.bold'))
            ->icon('<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>')
            ->tag('strong')
        );

        self::register('italic', InlineFormat::make('italic')
            ->title(t('editor.format.italic'))
            ->icon('<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4h-8z"/></svg>')
            ->tag('em')
        );

        self::register('underline', InlineFormat::make('underline')
            ->title(t('editor.format.underline'))
            ->icon('<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/></svg>')
            ->tag('u')
        );

        self::register('strikethrough', InlineFormat::make('strikethrough')
            ->title(t('editor.format.strikethrough'))
            ->icon('<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M10 19h4v-3h-4v3zM5 4v3h6v3h4V7h6V4H5zM3 14h18v-2H3v2z"/></svg>')
            ->tag('s')
        );

        self::register('code', InlineFormat::make('code')
            ->title(t('editor.format.code'))
            ->icon('<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>')
            ->tag('code')
        );

        self::register('link', InlineFormat::make('link')
            ->title(t('editor.format.link'))
            ->icon('<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>')
            ->tag('a')
            ->attribute('href', ['type' => 'string', 'default' => ''])
            ->attribute('target', ['type' => 'string', 'default' => '_blank'])
            ->attribute('rel', ['type' => 'string', 'default' => 'noopener noreferrer'])
            ->withRenderElement(function (array $attrs, Element|string $inner): Element {
                $a = Element::make('a');
                if (!empty($attrs['href'])) {
                    $a->attr('href', $attrs['href']);
                }
                if (!empty($attrs['target'])) {
                    $a->attr('target', $attrs['target']);
                }
                if (!empty($attrs['rel'])) {
                    $a->attr('rel', $attrs['rel']);
                }
                $a->child($inner);
                return $a;
            })
        );

        self::register('mention', InlineFormat::make('mention')
            ->title(t('editor.format.mention'))
            ->icon('<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>')
            ->tag('span')
            ->attribute('data-mention-id', ['type' => 'string', 'default' => ''])
            ->attribute('data-mention-name', ['type' => 'string', 'default' => ''])
            ->withRenderElement(function (array $attrs, Element|string $inner): Element {
                $span = Element::make('span')
                    ->class('ux-mention');
                if (!empty($attrs['data-mention-id'])) {
                    $span->data('mention-id', $attrs['data-mention-id']);
                }
                $span->child($inner);
                return $span;
            })
        );
    }
}
