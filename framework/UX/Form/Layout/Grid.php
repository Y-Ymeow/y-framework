<?php

declare(strict_types=1);

namespace Framework\UX\Form\Layout;

use Framework\UX\Form\Contracts\FormLayout;
use Framework\UX\Form\Concerns\HasComponents;
use Framework\View\Base\Element;

class Grid implements FormLayout
{
    use HasComponents;

    protected int $columns = 2;
    protected int $gap = 4;

    public static function make(int $columns = 2): static
    {
        $instance = new static();
        $instance->columns = $columns;
        return $instance;
    }

    public function columns(int $columns): static
    {
        $this->columns = max(1, min(12, $columns));
        return $this;
    }

    public function gap(int $gap): static
    {
        $this->gap = $gap;
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
        $grid = Element::make('div')
            ->class('grid', "gap-{$this->gap}", "grid-cols-{$this->columns}");

        foreach ($this->renderComponents() as $element) {
            $grid->child($element);
        }

        return $grid;
    }
}
