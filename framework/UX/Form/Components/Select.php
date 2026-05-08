<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\View\Base\Element;

class Select extends BaseField
{
    protected array $options = [];
    protected bool $multiple = false;

    public function getType(): string
    {
        return 'select';
    }

    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function render(): Element
    {
        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $select = Element::make('select')
            ->class('ux-form-input', ...$this->extraClasses)
            ->attr('id', $this->name)
            ->attr('name', $this->name);

        if ($this->submitMode) {
            $select->attr('data-submit-field', $this->name);
        } else {
            $select->attr('data-model', $this->name);
        }

        if ($this->multiple) {
            $select->attr('multiple', '');
        }

        if ($this->required) {
            $select->attr('required', '');
        }

        if ($this->disabled) {
            $select->attr('disabled', '');
        }

        $currentValue = $this->getValue();

        foreach ($this->options as $value => $label) {
            $option = Element::make('option')
                ->attr('value', (string)$value)
                ->text((string)$label);

            if ($this->isSelected($value, $currentValue)) {
                $option->attr('selected', '');
            }

            $select->child($option);
        }

        $wrapper->child($select);

        $help = $this->buildHelp();
        if ($help) {
            $wrapper->child($help);
        }

        return $wrapper;
    }

    protected function isSelected(mixed $optionValue, mixed $currentValue): bool
    {
        if ($this->multiple && is_array($currentValue)) {
            return in_array($optionValue, $currentValue, true);
        }

        return (string)$optionValue === (string)$currentValue;
    }
}
