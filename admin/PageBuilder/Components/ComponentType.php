<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components;

use Framework\UX\Form\FormBuilder;
use Framework\View\Base\Element;

abstract class ComponentType
{
    abstract public function name(): string;
    abstract public function label(): string;
    abstract public function icon(): string;
    abstract public function category(): string;

    /**
     * Define named slots for this component.
     * Each slot: ['name' => 'slot_name', 'label' => '显示名称']
     * Empty array = no slots (cannot accept child components).
     */
    public function slots(array $settings = []): array
    {
        return [];
    }

    /**
     * Maximum children per slot.
     * Key = slot name, Value = int limit (null = unlimited).
     */
    public function slotLimits(array $settings = []): array
    {
        return [];
    }

    /**
     * Return the target Element where slot children should be appended.
     * Override in components that render inner containers (e.g. Columns).
     */
    public function getSlotElement(Element $rendered, string $slotName): Element
    {
        return $rendered;
    }

    public function settings(FormBuilder $form): void
    {
    }

    public function defaultSettings(): array
    {
        $form = new FormBuilder();
        $this->settings($form);
        return $form->getDefaults();
    }

    public function styleTargets(): array
    {
        return ['root' => '根容器'];
    }

    public function applyStyles(Element $element, array $settings): void
    {
        $styles = $this->setting($settings, 'styles', []);
        $className = $this->setting($settings, 'className', '');

        if (empty($styles) && !empty($className)) {
            $styles = ['root' => $className];
        }

        if (empty($styles)) {
            return;
        }

        $this->applyStylesToElement($element, $styles);
    }

    protected function applyStylesToElement(Element $element, array $styles): void
    {
        $pbStyle = $element->getAttr('data-pb-style');

        if ($pbStyle !== null && isset($styles[$pbStyle]) && !empty($styles[$pbStyle])) {
            $element->class($styles[$pbStyle]);
        }

        foreach ($element->getChildren() as $child) {
            if ($child instanceof Element) {
                $this->applyStylesToElement($child, $styles);
            }
        }
    }

    abstract public function render(array $settings): Element;

    protected function setting(array $settings, string $key, mixed $fallback = ''): mixed
    {
        return $settings[$key] ?? $fallback;
    }
}
