<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\View\Base\Element;

class ImageBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('image');
        $this->title = t('editor.blocks.image');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>';
        $this->category = 'media';
        $this->attribute('src', ['type' => 'string', 'default' => '']);
        $this->attribute('alt', ['type' => 'string', 'default' => '']);
        $this->attribute('caption', ['type' => 'string', 'default' => '']);
        $this->attribute('align', ['type' => 'string', 'default' => 'center']);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $figure = Element::make('figure');

        $align = $attributes['align'] ?? 'center';
        $figure->style('text-align:' . $align);

        $img = Element::make('img')
            ->attr('src', $attributes['src'] ?? '')
            ->attr('alt', $attributes['alt'] ?? '');
        $figure->child($img);

        $caption = $attributes['caption'] ?? '';
        if ($caption) {
            $figure->child(
                Element::make('figcaption')->text($caption)
            );
        }

        return $figure;
    }
}
