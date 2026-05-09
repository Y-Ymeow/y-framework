<?php

declare(strict_types=1);

namespace Framework\UX\Form\Concerns;

use Framework\Component\Live\EmbeddedLiveComponent;
use Framework\View\Base\Element;

trait HasComponents
{
    protected array $components = [];

    public function schema(array|callable $components): static
    {
        if (is_callable($components)) {
            $components = $components();
        }
        $this->components = is_array($components) ? $components : [];
        return $this;
    }

    public function getComponents(): array
    {
        return $this->components;
    }

    public function hasComponents(): bool
    {
        return !empty($this->components);
    }

    public function addComponent($component): static
    {
        $this->components[] = $component;
        return $this;
    }

    protected function renderComponents(): array
    {
        $elements = [];
        foreach ($this->components as $component) {
            if (EmbeddedLiveComponent::isLiveComponent($component)) {
                if ($this instanceof EmbeddedLiveComponent) {
                    $component->setParent($this);
                }
                $component->_invoke();
                $elements[] = Element::make('div')->html($component->toHtml());
            } elseif (method_exists($component, 'render')) {
                $elements[] = $component->render();
            }
        }
        return $elements;
    }
}
