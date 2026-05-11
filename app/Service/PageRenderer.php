<?php

declare(strict_types=1);

namespace App\Service;

use Admin\PageBuilder\Components\ComponentRegistry;
use Admin\PageBuilder\PageBuilderCssService;
use Admin\PageBuilder\PageGenerator;
use Framework\Events\Hook;
use Framework\Events\ThemeBootingEvent;
use Framework\Events\ThemeBootedEvent;
use Framework\Http\Response\Response;
use Framework\Theme\ThemeManager;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

class PageRenderer
{
    public function render(string $name): ?Response
    {
        $generator = new PageGenerator();
        $tree = $generator->getComponentTree($name);

        if (empty($tree)) {
            return null;
        }

        $page = Element::make('div')->class('pb-page');
        $this->renderTree($tree, $page);

        $css = $this->buildPageCss($tree);
        AssetRegistry::getInstance()->addCssSnippet('pages', $css);

        $wrapped = $this->wrapWithTheme($page);

        return Response::html($wrapped);
    }

    public function renderTreeToElement(string $name): ?Element
    {
        $generator = new PageGenerator();
        $tree = $generator->getComponentTree($name);

        if (empty($tree)) {
            return null;
        }

        $page = Element::make('div')->class('pb-page');
        $this->renderTree($tree, $page);

        return $this->wrapWithTheme($page);
    }

    protected function wrapWithTheme(Element $content): Element
    {
        try {
            $manager = app(ThemeManager::class);
            $theme = $manager->getActiveThemeObject();
        } catch (\Throwable) {
            return $content;
        }

        if ($theme === null) {
            return $content;
        }

        $theme->boot();
        Hook::getInstance()->dispatch(new ThemeBootingEvent($theme));

        $cssVars = $theme->renderCssVariables();
        if ($cssVars) {
            AssetRegistry::getInstance()->addCssSnippet('theme-vars', ":root {\n{$cssVars}}");
        }

        foreach ($theme->getStyles() as $style) {
            AssetRegistry::getInstance()->css($style);
        }

        foreach ($theme->getScripts() as $script) {
            AssetRegistry::getInstance()->js($script);
        }

        $wrapper = Element::make('div')->class('theme-wrapper');
        $wrapper->child($theme->renderHeader());
        $wrapper->child($content);
        $wrapper->child($theme->renderFooter());

        Hook::getInstance()->dispatch(new ThemeBootedEvent($theme, $wrapper));

        return $wrapper;
    }

    protected function renderTree(array $tree, Element $parent): void
    {
        foreach ($tree as $component) {
            $type = $component['type'] ?? '';
            $settings = $component['settings'] ?? [];
            $children = $component['children'] ?? [];
            $slots = $component['slots'] ?? [];

            $componentType = ComponentRegistry::get($type);
            if (!$componentType) {
                continue;
            }

            $element = $componentType->render($settings);

            foreach ($slots as $slotName => $slotItems) {
                if (!empty($slotItems)) {
                    $slotEl = $componentType->getSlotElement($element, $slotName);
                    $this->renderTree($slotItems, $slotEl);
                }
            }

            if (!empty($children)) {
                $this->renderTree($children, $element);
            }

            $parent->child($element);
        }
    }

    protected function buildPageCss(array $tree): string
    {
        $cssService = new PageBuilderCssService();
        return $cssService->generateForTree($tree);
    }
}
