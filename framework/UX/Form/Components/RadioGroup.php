<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\View\Base\Element;

class RadioGroup extends BaseField
{
    protected array $options = [];
    protected bool $inline = false;

    public function getType(): string
    {
        return 'radio';
    }

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

    public function render(): Element
    {
        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $groupWrapper = Element::make('div')
            ->class('ux-form-radio-group', $this->inline ? 'ux-form-radio-group--inline' : '');

        $currentValue = $this->getValue();

        foreach ($this->options as $value => $label) {
            $radioLabel = Element::make('label')->class('ux-form-radio');

            $radio = Element::make('input')
                ->class('ux-form-radio-input', ...$this->extraClasses)
                ->attr('type', 'radio')
                ->attr('name', $this->name)
                ->attr('value', (string)$value);

            if ($this->submitMode) {
                $radio->attr('data-submit-field', $this->name);
            } else {
                $radio->attr('data-model', $this->name);
            }

            if ((string)$value === (string)$currentValue) {
                $radio->attr('checked', '');
            }

            if ($this->disabled) {
                $radio->attr('disabled', '');
            }

            $radioLabel->child($radio);
            $radioLabel->child(Element::make('span')->class('ux-form-radio-label')->text((string)$label));

            $groupWrapper->child($radioLabel);
        }

        $wrapper->child($groupWrapper);

        $help = $this->buildHelp();
        if ($help) {
            $wrapper->child($help);
        }

        return $wrapper;
    }
}
