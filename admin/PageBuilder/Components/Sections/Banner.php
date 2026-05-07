<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Sections;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\View\Base\Element;

class Banner extends ComponentType
{
    public function name(): string { return 'banner'; }
    public function label(): string { return '横幅公告'; }
    public function icon(): string { return 'megaphone'; }
    public function category(): string { return 'sections'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            TextInput::make('text')
                ->label('公告文字')
                ->default('🎉 新功能上线！'),
            TextInput::make('link_text')
                ->label('链接文字')
                ->default('查看详情'),
            TextInput::make('link_url')
                ->label('链接地址')
                ->default('#'),
            TextInput::make('className')
                ->label('额外样式')
                ->default(''),
        ]);
    }

    public function render(array $settings): Element
    {
        $text = $this->setting($settings, 'text', '🎉 新功能上线！');
        $linkText = $this->setting($settings, 'link_text', '查看详情');
        $linkUrl = $this->setting($settings, 'link_url', '#');
        $className = $this->setting($settings, 'className', '');

        $banner = Element::make('div')
            ->class('flex', 'items-center', 'justify-center', 'gap-4', 'px-8', 'py-3', 'bg-blue-50', 'border-b', 'border-blue-200', 'text-sm', 'text-blue-800', $className);

        $banner->child(Element::make('span')->text($text));

        if ($linkText) {
            $banner->child(
                Element::make('a')
                    ->class('font-semibold', 'text-blue-700', 'underline')
                    ->attr('href', $linkUrl)
                    ->text($linkText)
            );
        }

        return $banner;
    }
}
