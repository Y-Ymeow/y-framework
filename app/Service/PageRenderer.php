<?php

declare(strict_types=1);

namespace App\Service;

use Admin\PageBuilder\Components\ComponentRegistry;
use Admin\PageBuilder\PageBuilderCssService;
use Admin\PageBuilder\PageGenerator;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;
use Framework\View\Document\Document;

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

        return Response::html($page);
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

        return $page;
    }

    protected function renderTree(array $tree, Element $parent): void
    {
        foreach ($tree as $component) {
            $type = $component['type'] ?? '';
            $settings = $component['settings'] ?? [];
            $children = $component['children'] ?? [];

            $componentType = ComponentRegistry::get($type);
            if (!$componentType) {
                continue;
            }

            $element = $componentType->render($settings);

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
