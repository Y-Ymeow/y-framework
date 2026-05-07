<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Basic;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Textarea;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Layout\Grid;
use Framework\View\Base\Element;

class TextBlock extends ComponentType
{
    public function name(): string { return 'text_block'; }
    public function label(): string { return '文本'; }
    public function icon(): string { return 'justify-left'; }
    public function category(): string { return 'basic'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            Textarea::make('content')
                ->label('文本内容')
                ->rows(4)
                ->default(''),

            Grid::make(2)->schema([
                Select::make('align')
                    ->label('对齐')
                    ->options([
                        'left' => '左对齐',
                        'center' => '居中',
                        'right' => '右对齐',
                    ])
                    ->default('left'),

                TextInput::make('className')
                    ->label('额外样式')
                    ->default(''),
            ]),
        ]);
    }

    public function render(array $settings): Element
    {
        $content = $this->setting($settings, 'content', '');
        $align = $this->setting($settings, 'align', 'left');
        $className = $this->setting($settings, 'className', '');

        $alignClass = match ($align) {
            'center' => 'text-center',
            'right' => 'text-right',
            default => '',
        };

        return Element::make('div')
            ->class('pb-text-block', $alignClass, $className)
            ->html(nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')));
    }
}
