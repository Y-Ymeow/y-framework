<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\UX\RichEditor\SegmentRenderer;
use Framework\View\Base\Element;

class ColumnsBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('columns');
        $this->title = t('ux:editor.blocks.columns');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M3 3h8v18H3V3zm10 0h8v18h-8V3zm1 1v16h6V4h-6zM4 4v16h6V4H4z"/></svg>';
        $this->category = 'layout';
        $this->attribute('columnCount', ['type' => 'number', 'default' => 2, 'min' => 1, 'max' => 4]);
        $this->attribute('gap', ['type' => 'string', 'default' => '1rem']);
        $this->attribute('columns', ['type' => 'array', 'default' => [
            ['content' => []],
            ['content' => []],
        ]]);
        $this->supportsInnerBlocks(false);
        $this->supportsInlineFormats(true);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $columnCount = (int)($attributes['columnCount'] ?? 2);
        $gap = $attributes['gap'] ?? '1rem';
        $columns = $attributes['columns'] ?? [];

        $wrapper = Element::make('div')
            ->class('block-columns')
            ->style("display:grid;grid-template-columns:repeat({$columnCount},1fr);gap:{$gap}");

        for ($i = 0; $i < $columnCount; $i++) {
            $col = Element::make('div')->class('block-column');
            $content = $columns[$i]['content'] ?? [];

            if (is_array($content) && !empty($content) && isset($content[0])) {
                SegmentRenderer::appendSegmentsToElement($col, $content);
            } elseif (is_string($content) && $content !== '') {
                $col->html($content);
            }

            $wrapper->child($col);
        }

        return $wrapper;
    }
}
