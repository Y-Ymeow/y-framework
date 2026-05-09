<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\View\Base\Element;

class DividerBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('divider');
        $this->title = t('editor.blocks.divider');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M4 11h16v2H4z"/></svg>';
        $this->category = 'common';
        $this->attribute('style', ['type' => 'string', 'default' => 'solid']);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $hr = Element::make('hr');
        $style = $attributes['style'] ?? 'solid';
        if ($style !== 'solid') {
            $hr->style('border-top-style:' . $style);
        }
        return $hr;
    }
}
