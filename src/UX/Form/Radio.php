<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

class Radio extends FormField
{
    protected array $options = [];
    protected bool $inline = false;

    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $wrapperEl = Element::make('div');
        $wrapperEl->class('ux-form-radio-group');
        if ($this->inline) {
            $wrapperEl->class('ux-form-radio-inline');
        }

        foreach ($this->options as $value => $label) {
            $radioLabelEl = Element::make('label')->class('ux-form-radio');

            $inputEl = Element::make('input')
                ->attr('type', 'radio')
                ->attr('name', $this->name)
                ->attr('value', (string)$value)
                ->class('ux-form-input');

            if ($this->value !== null && (string)$this->value === (string)$value) {
                $inputEl->attr('checked', '');
            }

            $radioLabelEl->child($inputEl);
            $radioLabelEl->child(Element::make('span')->text($label));

            $wrapperEl->child($radioLabelEl);
        }

        $groupEl->child($wrapperEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
