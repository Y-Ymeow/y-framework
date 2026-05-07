<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\UX\Form\Contracts\FormField;
use Framework\UX\Form\Concerns\HasMeta;
use Framework\View\Base\Element;

abstract class BaseField implements FormField
{
    use HasMeta;

    protected string $name;
    protected string|array|null $label = null;
    protected mixed $value = null;
    protected mixed $default = null;
    protected bool $required = false;
    protected bool $disabled = false;
    protected bool $readonly = false;
    protected ?string $placeholder = null;
    protected ?string $help = null;
    protected array $rules = [];
    protected array $extraClasses = [];

    public static function make(string $name): static
    {
        $instance = new static();
        $instance->name = $name;
        return $instance;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getLabel(): string|array|null
    {
        return $this->label;
    }

    public function setLabel(string|array $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function label(string|array $label): static
    {
        return $this->setLabel($label);
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;
        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value ?? $this->default;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function value(mixed $value): static
    {
        return $this->setValue($value);
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function help(string $help): static
    {
        $this->help = $help;
        return $this;
    }

    public function getValidationRules(): array
    {
        return $this->rules;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;
        return $this;
    }

    public function class(string ...$classes): static
    {
        $this->extraClasses = array_merge($this->extraClasses, $classes);
        return $this;
    }

    protected function resolveLabel(): Element
    {
        if ($this->label === null) {
            return Element::make('span');
        }

        if (is_array($this->label)) {
            $key = $this->label[0] ?? '';
            $params = $this->label[1] ?? [];
            $default = $this->label[2] ?? '';
            return Element::make('span')->intl($key, $params, $default);
        }

        return Element::make('span')->text($this->label);
    }

    protected function buildWrapper(): Element
    {
        return Element::make('div')->class('ux-form-group');
    }

    protected function buildLabel(): Element
    {
        $labelEl = Element::make('label')
            ->class('ux-form-label')
            ->attr('for', $this->name)
            ->child($this->resolveLabel());

        if ($this->required) {
            $labelEl->child(Element::make('span')->class('ux-form-required')->text('*'));
        }

        return $labelEl;
    }

    protected function buildHelp(): ?Element
    {
        if (!$this->help) {
            return null;
        }

        return Element::make('span')->class('ux-form-help')->text($this->help);
    }
}
