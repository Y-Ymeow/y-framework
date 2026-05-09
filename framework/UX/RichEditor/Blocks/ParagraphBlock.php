<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\UX\RichEditor\SegmentRenderer;
use Framework\View\Base\Element;

class ParagraphBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('paragraph');
        $this->title = t('editor.blocks.paragraph');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9 16h2v-6h2v6h2V8H9v8zM5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>';
        $this->category = 'text';
        $this->attribute('content', ['type' => 'rich-text', 'default' => [], 'source' => 'children']);
        $this->supportsInlineFormats(true);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $el = Element::make('p');
        $content = $attributes['content'] ?? [];

        if (is_string($content)) {
            $el->html($content);
        } elseif (is_array($content) && !empty($content)) {
            SegmentRenderer::appendSegmentsToElement($el, $content);
        }

        return $el;
    }
}
