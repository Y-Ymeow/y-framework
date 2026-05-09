<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\UX\RichEditor\SegmentRenderer;
use Framework\View\Base\Element;

class HeadingBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('heading');
        $this->title = t('ux:editor.blocks.heading');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M5 4v3h5.5v12h3V7H19V4H5z"/></svg>';
        $this->category = 'text';
        $this->attribute('level', ['type' => 'number', 'default' => 2, 'min' => 1, 'max' => 6]);
        $this->attribute('content', ['type' => 'rich-text', 'default' => [], 'source' => 'children']);
        $this->supportsInlineFormats(true);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $level = 'h' . min(max((int)($attributes['level'] ?? 2), 1), 6);
        $el = Element::make($level);
        $content = $attributes['content'] ?? [];

        if (is_string($content)) {
            $el->html($content);
        } elseif (is_array($content) && !empty($content)) {
            SegmentRenderer::appendSegmentsToElement($el, $content);
        }

        return $el;
    }
}
