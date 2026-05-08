<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Basic;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Components\MediaPicker;
use Framework\UX\Form\Layout\Grid;
use Framework\View\Base\Element;

class ImageBlock extends ComponentType
{
    public function name(): string { return 'image'; }
    public function label(): string { return '图片'; }
    public function icon(): string { return 'image'; }
    public function category(): string { return 'basic'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            MediaPicker::make('src')
                ->label('图片')
                ->accept('image/*')
                ->maxSize(2048),

            TextInput::make('alt')
                ->label('替代文本')
                ->default(''),

            Grid::make(3)->schema([
                TextInput::make('width')
                    ->label('宽度')
                    ->placeholder('如 100% 或 300px')
                    ->default(''),

                Select::make('align')
                    ->label('对齐')
                    ->options([
                        'left' => '左对齐',
                        'center' => '居中',
                        'right' => '右对齐',
                    ])
                    ->default('center'),
            ])
        ]);
    }

    public function styleTargets(): array
    {
        return [
            'root' => '根容器',
            'image' => '图片',
        ];
    }

    public function render(array $settings): Element
    {
        $src = $this->setting($settings, 'src', '');
        $alt = $this->setting($settings, 'alt', '');
        $width = $this->setting($settings, 'width', '');
        $align = $this->setting($settings, 'align', 'center');

        $alignClass = match ($align) {
            'left' => 'pb-align-left',
            'right' => 'pb-align-right',
            default => 'pb-align-center',
        };

        $img = Element::make('img')
            ->class('pb-image')
            ->attr('data-pb-style', 'image')
            ->attr('src', $src)
            ->attr('alt', $alt)
            ->attr('loading', 'lazy');

        if ($width) {
            $img->attr('width', $width);
        }

        $wrapper = Element::make('div')
            ->class('pb-image-wrapper', $alignClass)
            ->attr('data-pb-style', 'root')
            ->child($img);

        $this->applyStyles($wrapper, $settings);

        return $wrapper;
    }
}
