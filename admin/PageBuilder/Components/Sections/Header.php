<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Sections;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;
use Framework\View\Base\Element;

class Header extends ComponentType
{
    public function name(): string { return 'header'; }
    public function label(): string { return '页头'; }
    public function icon(): string { return 'layout-navbar'; }
    public function category(): string { return 'sections'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            Grid::make(2)->schema([
                TextInput::make('logo_text')
                    ->label('Logo 文字')
                    ->default('My Site'),
                TextInput::make('cta_text')
                    ->label('CTA 按钮文字')
                    ->default('联系我们'),
            ]),
            TextInput::make('cta_url')
                ->label('CTA 按钮链接')
                ->default('#'),
            TextInput::make('nav_items')
                ->label('导航项 (逗号分隔)')
                ->default('首页,关于,服务,博客'),
            TextInput::make('className')
                ->label('额外样式')
                ->default(''),
        ]);
    }

    public function render(array $settings): Element
    {
        $logoText = $this->setting($settings, 'logo_text', 'My Site');
        $ctaText = $this->setting($settings, 'cta_text', '联系我们');
        $ctaUrl = $this->setting($settings, 'cta_url', '#');
        $navItems = $this->setting($settings, 'nav_items', '首页,关于,服务,博客');
        $className = $this->setting($settings, 'className', '');

        $header = Element::make('header')
            ->class('flex', 'items-center', 'justify-between', 'px-8', 'py-4', 'border-b', 'border-gray-200', $className);

        $header->child(
            Element::make('div')->class('text-xl', 'font-bold', 'text-gray-900')->text($logoText)
        );

        $nav = Element::make('ul')->class('flex', 'gap-6', 'list-none', 'm-0', 'p-0');
        foreach (explode(',', $navItems) as $item) {
            $item = trim($item);
            if ($item) {
                $nav->child(
                    Element::make('li')->child(
                        Element::make('a')
                            ->class('text-gray-500', 'no-underline', 'text-sm', 'font-medium', 'hover:text-gray-900')
                            ->attr('href', '#')
                            ->text($item)
                    )
                );
            }
        }
        $header->child($nav);

        if ($ctaText) {
            $header->child(
                Element::make('a')
                    ->class('px-5', 'py-2', 'text-sm', 'font-medium', 'bg-blue-600', 'text-white', 'rounded-md', 'no-underline', 'hover:bg-blue-700')
                    ->attr('href', $ctaUrl)
                    ->text($ctaText)
            );
        }

        return $header;
    }
}
