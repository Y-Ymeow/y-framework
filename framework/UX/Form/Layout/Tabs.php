<?php

declare(strict_types=1);

namespace Framework\UX\Form\Layout;

use Framework\UX\Form\Contracts\FormLayout;
use Framework\UX\Form\Concerns\HasComponents;
use Framework\View\Base\Element;

class Tabs implements FormLayout
{
    use HasComponents;

    protected string $activeTab = '';
    protected string $position = 'top';

    public static function make(): static
    {
        return new static();
    }

    public function active(string $tabId): static
    {
        $this->activeTab = $tabId;
        return $this;
    }

    public function position(string $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getName(): string
    {
        return '';
    }

    public function setName(string $name): static
    {
        return $this;
    }

    public function getLabel(): string|array|null
    {
        return null;
    }

    public function setLabel(string|array $label): static
    {
        return $this;
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function required(bool $required = true): static
    {
        return $this;
    }

    public function isDisabled(): bool
    {
        return false;
    }

    public function disabled(bool $disabled = true): static
    {
        return $this;
    }

    public function getValue(): mixed
    {
        return null;
    }

    public function setValue(mixed $value): static
    {
        return $this;
    }

    public function getDefault(): mixed
    {
        return null;
    }

    public function default(mixed $default): static
    {
        return $this;
    }

    public function render(): Element
    {
        $tabs = Element::make('div')
            ->class('ux-form-tabs', "ux-form-tabs--{$this->position}");

        $tabList = Element::make('div')->class('ux-form-tabs-list');

        $tabContent = Element::make('div')->class('ux-form-tabs-content');

        $firstTab = true;
        foreach ($this->components as $component) {
            if ($component instanceof Tab) {
                $tabId = $component->getId();
                $isActive = $firstTab || $tabId === $this->activeTab;

                $tabButton = Element::make('button')
                    ->class('ux-form-tabs-button', $isActive ? 'ux-form-tabs-button--active' : '')
                    ->attr('type', 'button')
                    ->attr('data-tab', $tabId)
                    ->text($component->getLabel() ?? $tabId);

                $tabList->child($tabButton);

                $tabPanel = Element::make('div')
                    ->class('ux-form-tabs-panel', $isActive ? 'ux-form-tabs-panel--active' : '')
                    ->attr('data-tab-panel', $tabId);

                if (method_exists($component, 'render')) {
                    $tabPanel->child($component->render());
                }

                $tabContent->child($tabPanel);

                $firstTab = false;
            }
        }

        $tabs->child($tabList);
        $tabs->child($tabContent);

        return $tabs;
    }
}
