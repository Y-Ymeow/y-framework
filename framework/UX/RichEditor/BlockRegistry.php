<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

use Framework\UX\RichEditor\Blocks\ParagraphBlock;
use Framework\UX\RichEditor\Blocks\HeadingBlock;
use Framework\UX\RichEditor\Blocks\ImageBlock;
use Framework\UX\RichEditor\Blocks\QuoteBlock;
use Framework\UX\RichEditor\Blocks\CodeBlock;
use Framework\UX\RichEditor\Blocks\ListBlock;
use Framework\UX\RichEditor\Blocks\DividerBlock;

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
        self::register('paragraph', new ParagraphBlock());
        self::register('heading', new HeadingBlock());
        self::register('image', new ImageBlock());
        self::register('quote', new QuoteBlock());
        self::register('code', new CodeBlock());
        self::register('list', new ListBlock());
        self::register('divider', new DividerBlock());
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
                'attributes' => ['content' => SegmentRenderer::htmlToSegments($html)],
            ];
        }

        return $blocks;
    }

    private static function domNodeToBlock(\DOMElement $node): ?array
    {
        $tag = strtolower($node->nodeName);
        $innerHtml = self::getInnerHtml($node);

        $blockMap = [
            'p' => ['paragraph', ['content' => SegmentRenderer::htmlToSegments($innerHtml)]],
            'h1' => ['heading', ['level' => 1, 'content' => SegmentRenderer::htmlToSegments($innerHtml)]],
            'h2' => ['heading', ['level' => 2, 'content' => SegmentRenderer::htmlToSegments($innerHtml)]],
            'h3' => ['heading', ['level' => 3, 'content' => SegmentRenderer::htmlToSegments($innerHtml)]],
            'h4' => ['heading', ['level' => 4, 'content' => SegmentRenderer::htmlToSegments($innerHtml)]],
            'h5' => ['heading', ['level' => 5, 'content' => SegmentRenderer::htmlToSegments($innerHtml)]],
            'h6' => ['heading', ['level' => 6, 'content' => SegmentRenderer::htmlToSegments($innerHtml)]],
            'blockquote' => ['quote', ['content' => SegmentRenderer::htmlToSegments($innerHtml)]],
            'pre' => ['code', ['content' => strip_tags($innerHtml)]],
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
            'attributes' => ['content' => SegmentRenderer::htmlToSegments($innerHtml)],
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
