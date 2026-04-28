<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

class Select extends FormField
{
    protected array $options = [];
    protected bool $multiple = false;
    protected string $emptyOption = '';

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

    public function emptyOption(string $text): static
    {
        $this->emptyOption = $text;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        return $this->emptyOption($placeholder);
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $selectEl = Element::make('select')
            ->class('ux-form-input');

        foreach ($this->buildFieldAttrs() as $key => $value) {
            $selectEl->attr($key, $value);
        }

        if ($this->multiple) {
            $selectEl->attr('multiple', '');
        }

        if ($this->emptyOption) {
            $selectEl->child(
                Element::make('option')
                    ->attr('value', '')
                    ->text($this->emptyOption)
            );
        }

        foreach ($this->options as $value => $label) {
            $optionEl = Element::make('option')
                ->attr('value', (string)$value)
                ->text($label);

            if ($this->value !== null && (string)$this->value === (string)$value) {
                $optionEl->attr('selected', '');
            }

            $selectEl->child($optionEl);
        }

        $groupEl->child($selectEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
