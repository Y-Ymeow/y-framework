<?php

declare(strict_types=1);

namespace Admin\PageBuilder;

use Admin\PageBuilder\Components\ComponentRegistry;
use Framework\CSS\CSSEngine;
use Framework\CSS\LayoutRules;
use Framework\CSS\SpacingRules;
use Framework\CSS\TypographyRules;
use Framework\CSS\ColorRules;
use Framework\CSS\BorderRules;
use Framework\CSS\EffectRules;
use Framework\CSS\InteractionRules;
use Framework\CSS\TransitionRules;
use Framework\CSS\TransformRules;
use Framework\CSS\AnimationRules;
use Framework\CSS\GradientRules;
use Framework\CSS\UtilityRules;
use Framework\View\Base\Element;

class PageBuilderCssService
{
    public function generateForTree(array $tree): string
    {
        $classes = [];
        $this->collectClassesFromTree($tree, $classes);
        return $this->buildCss($classes);
    }

    public function collectClassesFromTree(array $tree, array &$classes): void
    {
        foreach ($tree as $component) {
            $type = ComponentRegistry::get($component['type'] ?? '');
            if ($type) {
                $element = $type->render($component['settings'] ?? []);
                $this->extractClassesFromElement($element, $classes);
            }
            $slots = $component['slots'] ?? [];
            foreach ($slots as $slotItems) {
                if (!empty($slotItems)) {
                    $this->collectClassesFromTree($slotItems, $classes);
                }
            }
            if (!empty($component['children'])) {
                $this->collectClassesFromTree($component['children'], $classes);
            }
        }
    }

    private function extractClassesFromElement(Element $el, array &$classes): void
    {
        $classAttr = $el->getAttr('class') ?? '';
        foreach (explode(' ', $classAttr) as $cls) {
            $cls = trim($cls);
            if ($cls) $classes[$cls] = true;
        }
        foreach ($el->getChildren() as $child) {
            if ($child instanceof Element) {
                $this->extractClassesFromElement($child, $classes);
            }
        }
    }

    private function buildCss(array $classes): string
    {
        $rules = [];
        $parsers = [
            LayoutRules::class,
            SpacingRules::class,
            TypographyRules::class,
            ColorRules::class,
            BorderRules::class,
            EffectRules::class,
            InteractionRules::class,
            TransitionRules::class,
            TransformRules::class,
            AnimationRules::class,
            GradientRules::class,
            UtilityRules::class,
        ];

        foreach ($classes as $class => $_) {
            $parsed = CSSEngine::parseClass($class);
            foreach ($parsers as $parser) {
                if ($result = $parser::parse($parsed['base'], $parsed['pseudo'], $class)) {
                    if ($parsed['important']) {
                        $result = str_replace(';', ' !important;', $result);
                    }
                    $selector = CSSEngine::buildSelector($class, $parsed['pseudo']);
                    $rule = CSSEngine::buildRule($selector, $result, $parsed['media']);
                    $rules[] = $rule;
                    break;
                }
            }
        }

        return implode('', $rules);
    }
}
