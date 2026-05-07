<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Sections;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;
use Framework\View\Base\Element;

class Hero extends ComponentType
{
    public function name(): string { return 'hero'; }
    public function label(): string { return '首屏大图'; }
    public function icon(): string { return 'image-alt'; }
    public function category(): string { return 'sections'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            TextInput::make('title')
                ->label('主标题')
                ->default('欢迎来到我们的网站'),
            TextInput::make('subtitle')
                ->label('副标题')
                ->default('构建卓越的数字体验'),
            Grid::make(2)->schema([
                TextInput::make('button_text')
                    ->label('按钮文字')
                    ->default('了解更多'),
                TextInput::make('button_url')
                    ->label('按钮链接')
                    ->default('#'),
            ]),
            TextInput::make('background_image')
                ->label('背景图片 URL'),
            Grid::make(2)->schema([
                Select::make('align')
                    ->label('对齐')
                    ->options(['center' => '居中', 'left' => '左对齐'])
                    ->default('center'),
                Select::make('size')
                    ->label('尺寸')
                    ->options(['sm' => '小', 'md' => '中', 'lg' => '大'])
                    ->default('lg'),
            ]),
        ]);
    }

    public function render(array $settings): Element
    {
        $title = $this->setting($settings, 'title', '欢迎来到我们的网站');
        $subtitle = $this->setting($settings, 'subtitle', '');
        $buttonText = $this->setting($settings, 'button_text', '了解更多');
        $buttonUrl = $this->setting($settings, 'button_url', '#');
        $bgImage = $this->setting($settings, 'background_image', '');
        $align = $this->setting($settings, 'align', 'center');
        $size = $this->setting($settings, 'size', 'lg');
        $className = $this->setting($settings, 'className', '');

        $paddingClass = match ($size) {
            'sm' => 'p-8',
            'lg' => 'p-24',
            default => 'p-16',
        };

        $alignClass = $align === 'left' ? 'text-left' : 'text-center';

        $hero = Element::make('section')
            ->class('flex', 'items-center', 'justify-center', $alignClass, $paddingClass, 'bg-cover', 'bg-center', $className);

        if ($bgImage) {
            $hero->style("background-image:url({$bgImage})");
        }

        $content = Element::make('div')->class('max-w-3xl', $align === 'center' ? 'mx-auto' : '');

        $content->child(
            Element::make('h1')
                ->class('text-4xl', 'font-extrabold', 'mb-4', 'leading-tight', 'text-gray-900')
                ->text($title)
        );

        if ($subtitle) {
            $content->child(
                Element::make('p')
                    ->class('text-xl', 'text-gray-500', 'mb-8')
                    ->text($subtitle)
            );
        }

        if ($buttonText) {
            $content->child(
                Element::make('a')
                    ->class('inline-flex', 'px-8', 'py-3', 'text-base', 'font-semibold', 'bg-blue-600', 'text-white', 'rounded-lg', 'no-underline', 'hover:bg-blue-700')
                    ->attr('href', $buttonUrl)
                    ->text($buttonText)
            );
        }

        $hero->child($content);

        return $hero;
    }
}
