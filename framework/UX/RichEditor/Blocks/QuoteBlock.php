<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\UX\RichEditor\SegmentRenderer;
use Framework\View\Base\Element;

class QuoteBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('quote');
        $this->title = t('ux:editor.blocks.quote');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>';
        $this->category = 'text';
        $this->attribute('content', ['type' => 'rich-text', 'default' => [], 'source' => 'children']);
        $this->attribute('cite', ['type' => 'string', 'default' => '']);
        $this->supportsInlineFormats(true);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $blockquote = Element::make('blockquote');
        $content = $attributes['content'] ?? [];

        if (is_string($content)) {
            $blockquote->child(Element::make('p')->html($content));
        } elseif (is_array($content) && !empty($content)) {
            $p = Element::make('p');
            SegmentRenderer::appendSegmentsToElement($p, $content);
            $blockquote->child($p);
        }

        $cite = $attributes['cite'] ?? '';
        if ($cite) {
            $blockquote->child(Element::make('cite')->text($cite));
        }

        return $blockquote;
    }
}
