<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\UX\RichEditor\SegmentRenderer;
use Framework\View\Base\Element;

class ListBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('list');
        $this->title = t('ux:editor.blocks.list');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>';
        $this->category = 'text';
        $this->attribute('ordered', ['type' => 'boolean', 'default' => false]);
        $this->attribute('items', ['type' => 'array', 'default' => []]);
        $this->supportsInlineFormats(true);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $ordered = $attributes['ordered'] ?? false;
        $items = $attributes['items'] ?? [];

        $tag = $ordered ? 'ol' : 'ul';
        $list = Element::make($tag);

        foreach ($items as $item) {
            $li = Element::make('li');
            if (is_array($item) && isset($item['content'])) {
                $content = $item['content'];
                if (is_string($content)) {
                    $li->html($content);
                } elseif (is_array($content)) {
                    SegmentRenderer::appendSegmentsToElement($li, $content);
                }
                if (!empty($item['children'])) {
                    $childList = $this->renderElement(
                        ['ordered' => $ordered, 'items' => $item['children']],
                        []
                    );
                    $li->child($childList);
                }
            } elseif (is_array($item) && $this->isSegmentArray($item)) {
                SegmentRenderer::appendSegmentsToElement($li, $item);
            } else {
                $li->text((string)$item);
            }
            $list->child($li);
        }

        return $list;
    }

    private function isSegmentArray(array $arr): bool
    {
        return isset($arr[0]) && is_array($arr[0]) && array_key_exists('text', $arr[0]);
    }
}
