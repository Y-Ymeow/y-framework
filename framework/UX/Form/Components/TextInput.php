<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\View\Base\Element;

class TextInput extends BaseField
{
    protected string $type = 'text';
    protected ?string $inputType = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function inputType(string $type): static
    {
        $this->inputType = $type;
        return $this;
    }

    public function email(): static
    {
        return $this->inputType('email');
    }

    public function password(): static
    {
        return $this->inputType('password');
    }

    public function number(): static
    {
        return $this->inputType('number');
    }

    public function url(): static
    {
        return $this->inputType('url');
    }

    public function tel(): static
    {
        return $this->inputType('tel');
    }

    public function render(): Element
    {
        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $input = Element::make('input')
            ->class('ux-form-input', ...$this->extraClasses)
            ->attr('type', $this->inputType ?? $this->type)
            ->attr('id', $this->name)
            ->attr('name', $this->name)
            ->attr('data-model', $this->name);

        if ($this->placeholder) {
            $input->attr('placeholder', $this->placeholder);
        }

        if ($this->required) {
            $input->attr('required', '');
        }

        if ($this->disabled) {
            $input->attr('disabled', '');
        }

        if ($this->readonly) {
            $input->attr('readonly', '');
        }

        $value = $this->getValue();
        if ($value !== null) {
            $input->attr('value', (string)$value);
        }

        $wrapper->child($input);

        $help = $this->buildHelp();
        if ($help) {
            $wrapper->child($help);
        }

        return $wrapper;
    }
}
