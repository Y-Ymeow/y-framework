<?php

declare(strict_types=1);

namespace Framework\UX\Form\Layout;

use Framework\UX\Form\Contracts\FormLayout;
use Framework\UX\Form\Concerns\HasComponents;
use Framework\View\Base\Element;

class Tab implements FormLayout
{
    use HasComponents;

    protected string $id;
    protected string|array|null $label = null;
    protected ?string $icon = null;

    public static function make(string $id, string|array|null $label = null): static
    {
        $instance = new static();
        $instance->id = $id;
        $instance->label = $label;
        return $instance;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string|array|null
    {
        return $this->label;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getName(): string
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        $this->id = $name;
        return $this;
    }

    public function setLabel(string|array $label): static
    {
        $this->label = $label;
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
        $content = Element::make('div')->class('ux-form-tab-content');

        foreach ($this->renderComponents() as $element) {
            $content->child($element);
        }

        return $content;
    }
}
