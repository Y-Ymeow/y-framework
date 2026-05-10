<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\UX\RichEditor\SegmentRenderer;
use Framework\View\Base\Element;

class CalloutBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('callout');
        $this->title = t('ux:editor.blocks.callout');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>';
        $this->category = 'text';
        $this->attribute('type', ['type' => 'string', 'default' => 'info']);
        $this->attribute('title', ['type' => 'string', 'default' => '']);
        $this->attribute('content', ['type' => 'rich-text', 'default' => [], 'source' => 'children']);
        $this->supportsInlineFormats(true);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $type = $attributes['type'] ?? 'info';
        $title = $attributes['title'] ?? '';
        $content = $attributes['content'] ?? [];

        $colorMap = [
            'info' => ['#dbeafe', '#1e40af', 'ℹ️'],
            'warning' => ['#fef3c7', '#92400e', '⚠️'],
            'success' => ['#d1fae5', '#065f46', '✅'],
            'danger' => ['#fee2e2', '#991b1b', '❌'],
            'tip' => ['#ede9fe', '#5b21b6', '💡'],
        ];

        [$bg, $color, $icon] = $colorMap[$type] ?? $colorMap['info'];

        $callout = Element::make('div')
            ->class('callout', 'callout--' . $type)
            ->style("background:{$bg};border-left:4px solid {$color};padding:1rem 1.25rem;border-radius:0.375rem;margin:0.75rem 0");

        $header = Element::make('div')
            ->class('callout__header')
            ->style("display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;font-weight:600;color:{$color}");

        $header->child(Element::make('span')->class('callout__icon')->text($icon));

        if ($title) {
            $header->child(Element::make('span')->class('callout__title')->text($title));
        }

        $callout->child($header);

        $body = Element::make('div')->class('callout__body')->style("color:{$color};opacity:0.9");

        if (is_string($content) && $content !== '') {
            $body->html($content);
        } elseif (is_array($content) && !empty($content)) {
            SegmentRenderer::appendSegmentsToElement($body, $content);
        }

        $callout->child($body);

        return $callout;
    }
}
