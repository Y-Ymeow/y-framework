<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

/**
 * Block 注册表
 *
 * PHP 端注册 Block 类型，前端通过 data 属性获取定义。
 * 负责 Block 的序列化、反序列化和服务端渲染。
 *
 * @ux-category RichEditor
 * @ux-since 2.0.0
 */
class BlockRegistry
{
    private static array $blockTypes = [];
    private static array $categories = [];

    public static function register(string $name, BlockType $blockType): void
    {
        self::$blockTypes[$name] = $blockType;

        $category = $blockType->category;
        if (!isset(self::$categories[$category])) {
            self::$categories[$category] = [];
        }
        self::$categories[$category][] = $name;
    }

    public static function get(string $name): ?BlockType
    {
        return self::$blockTypes[$name] ?? null;
    }

    public static function has(string $name): bool
    {
        return isset(self::$blockTypes[$name]);
    }

    public static function all(): array
    {
        return self::$blockTypes;
    }

    public static function allDefinitions(): array
    {
        $definitions = [];
        foreach (self::$blockTypes as $name => $blockType) {
            $definitions[$name] = $blockType->toArray();
        }
        return $definitions;
    }

    public static function getByCategory(string $category): array
    {
        $names = self::$categories[$category] ?? [];
        $result = [];
        foreach ($names as $name) {
            if (isset(self::$blockTypes[$name])) {
                $result[$name] = self::$blockTypes[$name];
            }
        }
        return $result;
    }

    public static function getCategories(): array
    {
        return array_keys(self::$categories);
    }

    public static function remove(string $name): void
    {
        unset(self::$blockTypes[$name]);
        foreach (self::$categories as $category => $names) {
            self::$categories[$category] = array_diff($names, [$name]);
        }
    }

    public static function clear(): void
    {
        self::$blockTypes = [];
        self::$categories = [];
    }

    public static function registerCoreBlocks(): void
    {
        self::register('paragraph', BlockType::make('paragraph')
            ->title(t('editor.blocks.paragraph'))
            ->icon('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9 16h2v-6h2v6h2V8H9v8zM5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>')
            ->category('text')
            ->attribute('content', ['type' => 'rich-text', 'default' => '', 'source' => 'children'])
        );

        self::register('heading', BlockType::make('heading')
            ->title(t('editor.blocks.heading'))
            ->icon('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M5 4v3h5.5v12h3V7H19V4H5z"/></svg>')
            ->category('text')
            ->attribute('level', ['type' => 'number', 'default' => 2, 'min' => 1, 'max' => 6])
            ->attribute('content', ['type' => 'string', 'default' => '', 'source' => 'children'])
            ->render(function (array $attrs, array $innerBlocks): string {
                $level = min(max((int)($attrs['level'] ?? 2), 1), 6);
                $content = htmlspecialchars((string)($attrs['content'] ?? ''));
                return "<h{$level}>{$content}</h{$level}>";
            })
        );

        self::register('image', BlockType::make('image')
            ->title(t('editor.blocks.image'))
            ->icon('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>')
            ->category('media')
            ->attribute('src', ['type' => 'string', 'default' => ''])
            ->attribute('alt', ['type' => 'string', 'default' => ''])
            ->attribute('caption', ['type' => 'string', 'default' => ''])
            ->attribute('align', ['type' => 'string', 'default' => 'center'])
            ->render(function (array $attrs, array $innerBlocks): string {
                $src = htmlspecialchars((string)($attrs['src'] ?? ''));
                $alt = htmlspecialchars((string)($attrs['alt'] ?? ''));
                $align = htmlspecialchars((string)($attrs['align'] ?? 'center'));
                $caption = htmlspecialchars((string)($attrs['caption'] ?? ''));

                $figureStyle = match ($align) {
                    'left' => ' style="text-align:left"',
                    'right' => ' style="text-align:right"',
                    default => ' style="text-align:center"',
                };

                $html = "<figure{$figureStyle}><img src=\"{$src}\" alt=\"{$alt}\" />";
                if ($caption) {
                    $html .= "<figcaption>{$caption}</figcaption>";
                }
                $html .= '</figure>';
                return $html;
            })
        );

        self::register('quote', BlockType::make('quote')
            ->title(t('editor.blocks.quote'))
            ->icon('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>')
            ->category('text')
            ->attribute('content', ['type' => 'rich-text', 'default' => '', 'source' => 'children'])
            ->attribute('citation', ['type' => 'string', 'default' => ''])
            ->render(function (array $attrs, array $innerBlocks): string {
                $content = $attrs['content'] ?? '';
                $citation = htmlspecialchars((string)($attrs['citation'] ?? ''));
                $html = '<blockquote>' . $content;
                if ($citation) {
                    $html .= '<cite>' . $citation . '</cite>';
                }
                $html .= '</blockquote>';
                return $html;
            })
        );

        self::register('code', BlockType::make('code')
            ->title(t('editor.blocks.code'))
            ->icon('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>')
            ->category('text')
            ->attribute('content', ['type' => 'string', 'default' => ''])
            ->attribute('language', ['type' => 'string', 'default' => ''])
            ->render(function (array $attrs, array $innerBlocks): string {
                $content = htmlspecialchars((string)($attrs['content'] ?? ''));
                $lang = htmlspecialchars((string)($attrs['language'] ?? ''));
                $langAttr = $lang ? " class=\"language-{$lang}\"" : '';
                return "<pre><code{$langAttr}>{$content}</code></pre>";
            })
        );

        self::register('list', BlockType::make('list')
            ->title(t('editor.blocks.list'))
            ->icon('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4zM2 6h2v2H2zm0 5h2v2H2zm0 5h2v2H2z"/></svg>')
            ->category('text')
            ->attribute('ordered', ['type' => 'boolean', 'default' => false])
            ->attribute('items', ['type' => 'array', 'default' => []])
            ->render(function (array $attrs, array $innerBlocks): string {
                $ordered = (bool)($attrs['ordered'] ?? false);
                $items = (array)($attrs['items'] ?? []);
                $tag = $ordered ? 'ol' : 'ul';
                $html = "<{$tag}>";
                foreach ($items as $item) {
                    $html .= '<li>' . htmlspecialchars((string)$item) . '</li>';
                }
                $html .= "</{$tag}>";
                return $html;
            })
        );

        self::register('divider', BlockType::make('divider')
            ->title(t('editor.blocks.divider'))
            ->icon('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 13H5v-2h14v2z"/></svg>')
            ->category('common')
            ->render(fn(): string => '<hr />')
        );
    }

    public static function serialize(array $blocks): string
    {
        return json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function parse(string $json): array
    {
        if (empty($json)) {
            return [];
        }

        $blocks = json_decode($json, true);
        if (!is_array($blocks)) {
            return self::legacyHtmlToBlocks($json);
        }

        return $blocks;
    }

    public static function renderBlock(string $name, array $attributes = [], array $innerBlocks = []): string
    {
        $blockType = self::get($name);
        if (!$blockType) {
            return '<!-- unknown block: ' . htmlspecialchars($name) . ' -->';
        }

        return $blockType->renderBlock($attributes, $innerBlocks);
    }

    public static function render(array $blocks): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $name = $block['blockName'] ?? '';
            $attributes = $block['attributes'] ?? [];
            $innerBlocks = $block['innerBlocks'] ?? [];
            $html .= self::renderBlock($name, $attributes, $innerBlocks);
        }
        return $html;
    }

    public static function legacyHtmlToBlocks(string $html): array
    {
        $blocks = [];

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        foreach ($dom->childNodes as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            $block = self::domNodeToBlock($node);
            if ($block) {
                $blocks[] = $block;
            }
        }

        if (empty($blocks) && trim(strip_tags($html))) {
            $blocks[] = [
                'blockName' => 'paragraph',
                'attributes' => ['content' => $html],
            ];
        }

        return $blocks;
    }

    private static function domNodeToBlock(\DOMElement $node): ?array
    {
        $tag = strtolower($node->nodeName);

        $blockMap = [
            'p' => ['paragraph', ['content' => self::getInnerHtml($node)]],
            'h1' => ['heading', ['level' => 1, 'content' => self::getInnerHtml($node)]],
            'h2' => ['heading', ['level' => 2, 'content' => self::getInnerHtml($node)]],
            'h3' => ['heading', ['level' => 3, 'content' => self::getInnerHtml($node)]],
            'h4' => ['heading', ['level' => 4, 'content' => self::getInnerHtml($node)]],
            'h5' => ['heading', ['level' => 5, 'content' => self::getInnerHtml($node)]],
            'h6' => ['heading', ['level' => 6, 'content' => self::getInnerHtml($node)]],
            'blockquote' => ['quote', ['content' => self::getInnerHtml($node)]],
            'pre' => ['code', ['content' => self::getInnerHtml($node)]],
            'ul' => ['list', ['ordered' => false, 'items' => self::getListItems($node)]],
            'ol' => ['list', ['ordered' => true, 'items' => self::getListItems($node)]],
            'hr' => ['divider', []],
            'img' => ['image', [
                'src' => $node->getAttribute('src'),
                'alt' => $node->getAttribute('alt'),
            ]],
            'figure' => ['image', [
                'src' => self::getFigureImageSrc($node),
                'caption' => self::getFigureCaption($node),
            ]],
        ];

        if (isset($blockMap[$tag])) {
            [$blockName, $attributes] = $blockMap[$tag];
            return [
                'blockName' => $blockName,
                'attributes' => $attributes,
            ];
        }

        return [
            'blockName' => 'paragraph',
            'attributes' => ['content' => self::getInnerHtml($node)],
        ];
    }

    private static function getInnerHtml(\DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }
        return $html;
    }

    private static function getListItems(\DOMNode $node): array
    {
        $items = [];
        foreach ($node->childNodes as $child) {
            if ($child->nodeName === 'li') {
                $items[] = trim($child->textContent);
            }
        }
        return $items;
    }

    private static function getFigureImageSrc(\DOMElement $node): string
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->nodeName === 'img') {
                return $child->getAttribute('src');
            }
        }
        return '';
    }

    private static function getFigureCaption(\DOMElement $node): string
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeName === 'figcaption') {
                return trim($child->textContent);
            }
        }
        return '';
    }
}
