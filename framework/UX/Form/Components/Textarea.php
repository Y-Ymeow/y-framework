<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\View\Base\Element;

class Textarea extends BaseField
{
    protected int $rows = 4;
    protected int $cols = 0;

    public function getType(): string
    {
        return 'textarea';
    }

    public function rows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    public function cols(int $cols): static
    {
        $this->cols = $cols;
        return $this;
    }

    public function render(): Element
    {
        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $textarea = Element::make('textarea')
            ->class('ux-form-input', ...$this->extraClasses)
            ->attr('id', $this->name)
            ->attr('name', $this->name)
            ->attr('rows', (string)$this->rows);

        if ($this->submitMode) {
            $textarea->attr('data-submit-field', $this->name);
        } else {
            $textarea->attr('data-model', $this->name);
        }

        if ($this->cols > 0) {
            $textarea->attr('cols', (string)$this->cols);
        }

        if ($this->placeholder) {
            $textarea->attr('placeholder', $this->placeholder);
        }

        if ($this->required) {
            $textarea->attr('required', '');
        }

        if ($this->disabled) {
            $textarea->attr('disabled', '');
        }

        if ($this->readonly) {
            $textarea->attr('readonly', '');
        }

        $value = $this->getValue();
        $textarea->text((string)($value ?? ''));

        $wrapper->child($textarea);

        $help = $this->buildHelp();
        if ($help) {
            $wrapper->child($help);
        }

        return $wrapper;
    }
}
