<?php

declare(strict_types=1);

namespace Theme\Default;

use Framework\Theme\Theme as BaseTheme;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

class Theme extends BaseTheme
{
    public function boot(): void
    {
        $cssVars = $this->renderCssVariables();
        if ($cssVars) {
            AssetRegistry::getInstance()->addCssSnippet('theme-vars', ":root {\n{$cssVars}}");
        }

        if (file_exists($this->path . '/assets/css/style.css')) {
            AssetRegistry::getInstance()->css($this->asset('css/style.css'), 'theme-style');
        }
    }

    public function getStyles(): array
    {
        return [
            $this->asset('css/style.css'),
        ];
    }

    public function renderHeader(): Element
    {
        $primary = $this->getSetting('primary_color', '#3b82f6');
        $position = $this->getSetting('sidebar_position', 'left');
        $showBreadcrumb = $this->getSetting('show_breadcrumb', true);

        $header = Element::make('header')->class('theme-header');

        $nav = Element::make('nav')->class('theme-nav');
        $nav->child(
            Element::make('div')->class('theme-brand')->child(
                Element::make('a')->attr('href', '/')->class('theme-logo')->text('My Site')
            )
        );

        $menu = Element::make('ul')->class('theme-menu');
        $menu->child(Element::make('li')->child(
            Element::make('a')->attr('href', '/')->class('theme-menu-link')->text('首页')
        ));
        $menu->child(Element::make('li')->child(
            Element::make('a')->attr('href', '/about')->class('theme-menu-link')->text('关于')
        ));
        $nav->child($menu);
        $header->child($nav);

        if ($showBreadcrumb) {
            $header->child(
                Element::make('div')->class('theme-breadcrumb')->text('首页')
            );
        }

        return $header;
    }

    public function renderFooter(): Element
    {
        $footer = Element::make('footer')->class('theme-footer');
        $footer->child(
            Element::make('div')->class('theme-footer-inner')->children(
                Element::make('div')->class('theme-footer-copyright')->text('© 2024 My Site. All rights reserved.'),
                Element::make('div')->class('theme-footer-powered')->text('Powered by Framework')
            )
        );
        return $footer;
    }
}