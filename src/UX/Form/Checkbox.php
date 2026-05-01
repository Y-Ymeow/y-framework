<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

class Checkbox extends FormField
{
    protected bool $checked = false;

    public function checked(bool $checked = true): static
    {
        $this->checked = $checked;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = Element::make('label')->class('ux-form-checkbox');

        $inputEl = Element::make('input')
            ->attr('type', 'checkbox')
            ->attr('name', $this->name)
            ->attr('id', $this->name)
            ->attr('value', '1')
            ->class('ux-form-input');

        if ($this->checked || $this->value) {
            $inputEl->attr('checked', '');
        }

        foreach ($this->buildFieldAttrs() as $key => $value) {
            if ($key !== 'name' && $key !== 'id') {
                $inputEl->attr($key, $value);
            }
        }

        if ($this->liveModel) {
            $inputEl->attr('data-live-model', $this->liveModel);
        }

        $labelEl->child($inputEl);
        $labelEl->child(Element::make('span')->text($this->label));

        $groupEl->child($labelEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
