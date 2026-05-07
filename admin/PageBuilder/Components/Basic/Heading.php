<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Basic;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;
use Framework\View\Base\Element;

class Heading extends ComponentType
{
    public function name(): string
    {
        return 'heading';
    }
    public function label(): string
    {
        return '标题';
    }
    public function icon(): string
    {
        return 'type-h1';
    }
    public function category(): string
    {
        return 'basic';
    }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            TextInput::make('text')
                ->label('标题内容')
                ->placeholder('输入标题')
                ->default(''),

            Grid::make(3)->schema([
                Select::make('level')
                    ->label('级别')
                    ->options([
                        'h1' => 'H1',
                        'h2' => 'H2',
                        'h3' => 'H3',
                        'h4' => 'H4',
                    ])
                    ->default('h2'),

                Select::make('align')
                    ->label('对齐')
                    ->options([
                        'left' => '左对齐',
                        'center' => '居中',
                        'right' => '右对齐',
                    ])
                    ->default('left'),
            ]),
        ]);
    }

    public function render(array $settings): Element
    {
        $level = $this->setting($settings, 'level', 'h2');
        $text = $this->setting($settings, 'text', '');
        $align = $this->setting($settings, 'align', 'left');
        $className = $this->setting($settings, 'className', '');

        $alignClass = match ($align) {
            'center' => 'text-center',
            'right' => 'text-right',
            default => '',
        };

        return Element::make($level)
            ->class('pb-heading', $alignClass, $className)
            ->text($text);
    }
}
