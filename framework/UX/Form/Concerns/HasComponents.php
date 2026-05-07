<?php

declare(strict_types=1);

namespace Framework\UX\Form\Concerns;

trait HasComponents
{
    protected array $components = [];

    public function schema(array $components): static
    {
        $this->components = $components;
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
            if (method_exists($component, 'render')) {
                $elements[] = $component->render();
            }
        }
        return $elements;
    }
}
