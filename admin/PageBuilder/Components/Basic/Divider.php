<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Basic;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;
use Framework\View\Base\Element;

class Divider extends ComponentType
{
    public function name(): string { return 'divider'; }
    public function label(): string { return '分隔线'; }
    public function icon(): string { return 'dash'; }
    public function category(): string { return 'basic'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            Grid::make(2)->schema([
                Select::make('style')
                    ->label('样式')
                    ->options([
                        'solid' => '实线',
                        'dashed' => '虚线',
                        'dotted' => '点线',
                    ])
                    ->default('solid'),

                Select::make('spacing')
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
        $style = $this->setting($settings, 'style', 'solid');
        $spacing = $this->setting($settings, 'spacing', 'md');

        $spacingClass = match ($spacing) {
            'sm' => 'pb-divider-sm',
            'lg' => 'pb-divider-lg',
            default => 'pb-divider-md',
        };

        return Element::make('hr')
            ->class('pb-divider', "pb-divider-{$style}", $spacingClass);
    }
}
