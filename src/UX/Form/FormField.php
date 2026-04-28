<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

abstract class FormField extends UXComponent
{
    protected string $name = '';
    protected string $label = '';
    protected bool $required = false;
    protected mixed $value = null;
    protected string $placeholder = '';
    protected string $help = '';
    protected bool $disabled = false;
    protected bool $readonly = false;
    protected string $autocomplete = '';
    protected array $rules = [];

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    public function value(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function help(string $help): static
    {
        $this->help = $help;
        return $this;
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

    public function autocomplete(string $autocomplete): static
    {
        $this->autocomplete = $autocomplete;
        return $this;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;
        return $this;
    }

    protected function buildFieldAttrs(): array
    {
        $attrs = [];
        $attrs['id'] = $this->name;
        $attrs['name'] = $this->name;

        if ($this->placeholder) {
            $attrs['placeholder'] = $this->placeholder;
        }

        if ($this->required) {
            $attrs['required'] = 'required';
        }

        if ($this->disabled) {
            $attrs['disabled'] = 'disabled';
        }

        if ($this->readonly) {
            $attrs['readonly'] = 'readonly';
        }

        if ($this->autocomplete) {
            $attrs['autocomplete'] = $this->autocomplete;
        }

        return $attrs;
    }

    protected function renderLabel(): ?Element
    {
        if (!$this->label) return null;

        $labelEl = Element::make('label')
            ->class('ux-form-label')
            ->attr('for', $this->name)
            ->text($this->label);

        if ($this->required) {
            $labelEl->child(Element::make('span')->class('ux-form-required')->text('*'));
        }

        return $labelEl;
    }

    protected function renderHelp(): ?Element
    {
        if (!$this->help) return null;

        return Element::make('div')->class('ux-form-help')->html($this->help);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
