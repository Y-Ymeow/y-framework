<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Layout;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;
use Framework\View\Base\Element;

class Columns extends ComponentType
{
    public function name(): string { return 'columns'; }
    public function label(): string { return '多列'; }
    public function icon(): string { return 'layout-split'; }
    public function category(): string { return 'layout'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            Grid::make(3)->schema([
                Select::make('count')
                    ->label('列数')
                    ->options([
                        '2' => '2列',
                        '3' => '3列',
                    ])
                    ->default('2'),

                Select::make('ratio')
                    ->label('比例')
                    ->options([
                        'equal' => '等宽',
                        'sidebar' => '侧边栏(1:2)',
                        'wide' => '宽侧边栏(2:1)',
                    ])
                    ->default('equal'),

            ])
        ]);
    }

    public function render(array $settings): Element
    {
        $count = (int)$this->setting($settings, 'count', '2');
        $ratio = $this->setting($settings, 'ratio', 'equal');
        $className = $this->setting($settings, 'className', '');

        $ratioClass = match ($ratio) {
            'sidebar' => 'pb-columns-sidebar',
            'wide' => 'pb-columns-wide',
            default => 'pb-columns-equal',
        };

        $row = Element::make('div')
            ->class('pb-columns', "pb-columns-{$count}", $ratioClass, $className);

        for ($i = 0; $i < $count; $i++) {
            $col = Element::make('div')
                ->class('pb-column', "pb-column-{$i}");
            $row->child($col);
        }

        return $row;
    }

    public function isContainer(): bool { return true; }
}
