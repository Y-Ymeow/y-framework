<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\View\Base\Element;

class CodeBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('code');
        $this->title = t('editor.blocks.code');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>';
        $this->category = 'text';
        $this->attribute('content', ['type' => 'string', 'default' => '']);
        $this->attribute('language', ['type' => 'string', 'default' => '']);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $pre = Element::make('pre');

        $language = $attributes['language'] ?? '';
        $code = Element::make('code');
        if ($language) {
            $code->class('language-' . $language);
        }
        $code->text($attributes['content'] ?? '');

        $pre->child($code);
        return $pre;
    }
}
