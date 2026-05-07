<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\View\Base\Element;

class Checkbox extends BaseField
{
    protected mixed $checkedValue = '1';
    protected mixed $uncheckedValue = '0';

    public function getType(): string
    {
        return 'checkbox';
    }

    public function checkedValue(mixed $value): static
    {
        $this->checkedValue = $value;
        return $this;
    }

    public function uncheckedValue(mixed $value): static
    {
        $this->uncheckedValue = $value;
        return $this;
    }

    public function render(): Element
    {
        $wrapper = $this->buildWrapper();

        $labelWrapper = Element::make('label')->class('ux-form-checkbox');

        $input = Element::make('input')
            ->class('ux-form-checkbox-input', ...$this->extraClasses)
            ->attr('type', 'checkbox')
            ->attr('id', $this->name)
            ->attr('name', $this->name)
            ->attr('value', (string)$this->checkedValue)
            ->attr('data-model', $this->name);

        if ($this->isChecked()) {
            $input->attr('checked', '');
        }

        if ($this->disabled) {
            $input->attr('disabled', '');
        }

        $labelWrapper->child($input);
        $labelWrapper->child($this->resolveLabel());

        $wrapper->child($labelWrapper);

        $help = $this->buildHelp();
        if ($help) {
            $wrapper->child($help);
        }

        return $wrapper;
    }

    protected function isChecked(): bool
    {
        $value = $this->getValue();
        return $value === $this->checkedValue || $value === true || $value === 1 || $value === '1';
    }
}
