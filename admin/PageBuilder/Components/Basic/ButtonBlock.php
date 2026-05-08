<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Basic;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;
use Framework\View\Base\Element;

class ButtonBlock extends ComponentType
{
    public function name(): string { return 'button'; }
    public function label(): string { return '按钮'; }
    public function icon(): string { return 'cursor'; }
    public function category(): string { return 'basic'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            Grid::make(2)->schema([
                TextInput::make('text')
                    ->label('按钮文字')
                    ->default('点击'),

                TextInput::make('url')
                    ->label('链接')
                    ->default(''),
            ]),

            Grid::make(3)->schema([
                Select::make('variant')
                    ->label('样式')
                    ->options([
                        'primary' => '主要',
                        'secondary' => '次要',
                        'outline' => '描边',
                    ])
                    ->default('primary'),

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

    public function styleTargets(): array
    {
        return [
            'root' => '根容器',
            'button' => '按钮',
        ];
    }

    public function render(array $settings): Element
    {
        $text = $this->setting($settings, 'text', '点击');
        $url = $this->setting($settings, 'url', '');
        $variant = $this->setting($settings, 'variant', 'primary');
        $align = $this->setting($settings, 'align', 'left');

        $variantClass = match ($variant) {
            'secondary' => 'pb-btn-secondary',
            'outline' => 'pb-btn-outline',
            default => 'pb-btn-primary',
        };

        $alignClass = match ($align) {
            'center' => 'pb-align-center',
            'right' => 'pb-align-right',
            default => '',
        };

        $tag = $url ? 'a' : 'button';
        $btn = Element::make($tag)
            ->class('pb-btn', $variantClass)
            ->attr('data-pb-style', 'button')
            ->text($text);

        if ($url) {
            $btn->attr('href', $url);
        }

        $wrapper = Element::make('div')
            ->class('pb-button-wrapper', $alignClass)
            ->attr('data-pb-style', 'root')
            ->child($btn);

        $this->applyStyles($wrapper, $settings);

        return $wrapper;
    }
}
