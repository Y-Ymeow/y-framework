<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Layout;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid as FormGrid;
use Framework\View\Base\Element;

class GridContainer extends ComponentType
{
    public function name(): string { return 'grid'; }
    public function label(): string { return '网格'; }
    public function icon(): string { return 'grid-3x3-gap'; }
    public function category(): string { return 'layout'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            FormGrid::make(3)->schema([
                Select::make('columns')
                    ->label('列数')
                    ->options([
                        '2' => '2列',
                        '3' => '3列',
                        '4' => '4列',
                    ])
                    ->default('2'),

                Select::make('gap')
                    ->label('间距')
                    ->options([
                        'sm' => '小',
                        'md' => '中',
                        'lg' => '大',
                    ])
                    ->default('md'),
            ]),
        ]);
    }

    public function render(array $settings): Element
    {
        $columns = (int)$this->setting($settings, 'columns', '2');
        $gap = $this->setting($settings, 'gap', 'md');

        $gapClass = match ($gap) {
            'sm' => 'pb-grid-gap-sm',
            'lg' => 'pb-grid-gap-lg',
            default => 'pb-grid-gap-md',
        };

        $el = Element::make('div')
            ->class('pb-grid', "pb-grid-{$columns}", $gapClass)
            ->attr('data-pb-style', 'root');

        $this->applyStyles($el, $settings);

        return $el;
    }

    public function isContainer(): bool { return true; }
}
