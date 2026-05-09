<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

use Framework\View\Base\Element;

class SegmentRenderer
{
    public static function renderSegments(array $segments): array
    {
        $children = [];
        foreach ($segments as $segment) {
            $rendered = self::renderSegment($segment);
            if ($rendered !== null) {
                $children[] = $rendered;
            }
        }
        return $children;
    }

    public static function renderSegment(array $segment): Element|string|null
    {
        $text = $segment['text'] ?? '';
        $formatKeys = array_diff(array_keys($segment), ['text']);

        if (empty($formatKeys)) {
            return $text;
        }

        $inner = $text;
        foreach (array_reverse($formatKeys) as $formatName) {
            $formatValue = $segment[$formatName];
            $format = InlineFormatRegistry::get($formatName);
            if (!$format) {
                continue;
            }

            $formatAttrs = is_array($formatValue) ? $formatValue : [];
            $inner = $format->renderElement($formatAttrs, $inner);
        }

        return $inner;
    }

    public static function appendSegmentsToElement(Element $el, array $segments): void
    {
        foreach ($segments as $segment) {
            $rendered = self::renderSegment($segment);
            if ($rendered !== null) {
                $el->child($rendered);
            }
        }
    }

    public static function segmentsToHtml(array $segments): string
    {
        $html = '';
        foreach ($segments as $segment) {
            $rendered = self::renderSegment($segment);
            if ($rendered instanceof Element) {
                $html .= $rendered->render();
            } elseif (is_string($rendered)) {
                $html .= $rendered;
            }
        }
        return $html;
    }

    public static function htmlToSegments(string $html): array
    {
        if (trim($html) === '') {
            return [['text' => '']];
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $container = null;
        foreach ($dom->childNodes as $node) {
            if ($node instanceof \DOMElement && $node->nodeName === 'div') {
                $container = $node;
                break;
            }
        }

        if (!$container) {
            return [['text' => $html]];
        }

        $segments = [];
        self::walkDomNode($container, [], $segments);

        if (empty($segments)) {
            $segments[] = ['text' => ''];
        }

        return $segments;
    }

    private static function walkDomNode(\DOMNode $node, array $activeFormats, array &$segments): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = $child->textContent;
                if ($text !== '') {
                    $segment = ['text' => $text];
                    foreach ($activeFormats as $name => $attrs) {
                        $segment[$name] = $attrs;
                    }
                    $segments[] = $segment;
                }
            } elseif ($child instanceof \DOMElement) {
                $formatInfo = self::domElementToFormat($child);
                if ($formatInfo) {
                    $newFormats = $activeFormats;
                    $newFormats[$formatInfo['name']] = $formatInfo['attrs'];
                    self::walkDomNode($child, $newFormats, $segments);
                } else {
                    self::walkDomNode($child, $activeFormats, $segments);
                }
            }
        }
    }

    private static function domElementToFormat(\DOMElement $el): ?array
    {
        $tag = strtolower($el->nodeName);

        $tagMap = [
            'strong' => 'bold',
            'b' => 'bold',
            'em' => 'italic',
            'i' => 'italic',
            'u' => 'underline',
            's' => 'strikethrough',
            'del' => 'strikethrough',
            'code' => 'code',
            'a' => 'link',
        ];

        if (isset($tagMap[$tag])) {
            $name = $tagMap[$tag];
            $attrs = [];

            if ($name === 'link') {
                if ($el->hasAttribute('href')) $attrs['href'] = $el->getAttribute('href');
                if ($el->hasAttribute('target')) $attrs['target'] = $el->getAttribute('target');
                if ($el->hasAttribute('rel')) $attrs['rel'] = $el->getAttribute('rel');
            }

            return ['name' => $name, 'attrs' => $attrs];
        }

        $formatName = $el->getAttribute('data-format');
        if ($formatName && InlineFormatRegistry::has($formatName)) {
            $attrs = [];
            foreach ($el->attributes as $attr) {
                if (str_starts_with($attr->name, 'data-') && $attr->name !== 'data-format') {
                    $key = $attr->name;
                    $attrs[$key] = $attr->value;
                }
            }
            return ['name' => $formatName, 'attrs' => $attrs];
        }

        if ($el->hasAttribute('class')) {
            $classes = explode(' ', $el->getAttribute('class'));
            if (in_array('ux-mention', $classes)) {
                $attrs = [];
                if ($el->hasAttribute('data-mention-id')) {
                    $attrs['data-mention-id'] = $el->getAttribute('data-mention-id');
                }
                return ['name' => 'mention', 'attrs' => $attrs];
            }
        }

        return null;
    }
}
