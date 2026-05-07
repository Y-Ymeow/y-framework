<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Sections;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\View\Base\Element;

class Footer extends ComponentType
{
    public function name(): string { return 'footer'; }
    public function label(): string { return '页脚'; }
    public function icon(): string { return 'window-dock'; }
    public function category(): string { return 'sections'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            TextInput::make('logo_text')
                ->label('Logo 文字')
                ->default('My Site'),
            TextInput::make('links')
                ->label('链接 (逗号分隔)')
                ->default('关于我们,隐私政策,服务条款,联系我们'),
            TextInput::make('copyright')
                ->label('版权信息')
                ->default('© 2025 My Site. All rights reserved.'),
            TextInput::make('className')
                ->label('额外样式')
                ->default(''),
        ]);
    }

    public function render(array $settings): Element
    {
        $logoText = $this->setting($settings, 'logo_text', 'My Site');
        $linksRaw = $this->setting($settings, 'links', '');
        $copyright = $this->setting($settings, 'copyright', '© 2025 My Site. All rights reserved.');
        $className = $this->setting($settings, 'className', '');

        $footer = Element::make('footer')
            ->class('py-8', 'px-8', 'border-t', 'border-gray-200', 'bg-gray-50', $className);

        $content = Element::make('div')
            ->class('flex', 'justify-between', 'items-center');

        $content->child(
            Element::make('div')->class('text-xl', 'font-bold', 'text-gray-900')->text($logoText)
        );

        $linksList = Element::make('ul')
            ->class('flex', 'gap-6', 'list-none', 'm-0', 'p-0');
        foreach (explode(',', $linksRaw) as $link) {
            $link = trim($link);
            if ($link) {
                $linksList->child(
                    Element::make('li')->child(
                        Element::make('a')
                            ->class('text-gray-500', 'no-underline', 'text-sm', 'hover:text-gray-900')
                            ->attr('href', '#')
                            ->text($link)
                    )
                );
            }
        }
        $content->child($linksList);

        $footer->child($content);
        $footer->child(
            Element::make('div')->class('text-xs', 'text-gray-400', 'mt-4')->text($copyright)
        );

        return $footer;
    }
}
