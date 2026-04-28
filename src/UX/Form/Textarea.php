<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

class Textarea extends FormField
{
    protected int $rows = 4;

    public function rows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $textareaEl = Element::make('textarea')
            ->class('ux-form-input')
            ->attr('rows', (string)$this->rows);

        foreach ($this->buildFieldAttrs() as $key => $value) {
            $textareaEl->attr($key, $value);
        }

        $textareaEl->text((string)($this->value ?? ''));

        $groupEl->child($textareaEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
