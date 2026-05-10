<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\View\Base\Element;

class TableBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('table');
        $this->title = t('ux:editor.blocks.table');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M3 3v18h18V3H3zm8 16H5v-4h6v4zm0-6H5V9h6v4zm0-6H5V5h6v4zm8 12h-6v-4h6v4zm0-6h-6V9h6v4zm0-6h-6V5h6v4z"/></svg>';
        $this->category = 'text';
        $this->attribute('headers', ['type' => 'array', 'default' => ['列1', '列2', '列3']]);
        $this->attribute('rows', ['type' => 'array', 'default' => [['', '', '']]]);
        $this->attribute('hasHeader', ['type' => 'boolean', 'default' => true]);
        $this->attribute('striped', ['type' => 'boolean', 'default' => true]);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $headers = $attributes['headers'] ?? [];
        $rows = $attributes['rows'] ?? [];
        $hasHeader = $attributes['hasHeader'] ?? true;
        $striped = $attributes['striped'] ?? true;

        $table = Element::make('table')
            ->class('block-table')
            ->style('width:100%;border-collapse:collapse;margin:0.75rem 0;font-size:0.875rem');

        if ($hasHeader && !empty($headers)) {
            $thead = Element::make('thead');
            $tr = Element::make('tr');
            foreach ($headers as $header) {
                $tr->child(
                    Element::make('th')
                        ->style('padding:0.5rem 0.75rem;border:1px solid #d1d5db;background:#f3f4f6;text-align:left;font-weight:600')
                        ->text((string)$header)
                );
            }
            $thead->child($tr);
            $table->child($thead);
        }

        $tbody = Element::make('tbody');
        foreach ($rows as $i => $row) {
            $bg = ($striped && $i % 2 === 1) ? 'background:#f9fafb;' : '';
            $tr = Element::make('tr')->style($bg);
            foreach ($row as $cell) {
                $tr->child(
                    Element::make('td')
                        ->style('padding:0.5rem 0.75rem;border:1px solid #d1d5db')
                        ->text((string)$cell)
                );
            }
            $tbody->child($tr);
        }
        $table->child($tbody);

        return $table;
    }
}
