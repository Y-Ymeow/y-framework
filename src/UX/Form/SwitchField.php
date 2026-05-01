<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

class SwitchField extends FormField
{
    protected bool $checked = false;
    protected string $onText = '';
    protected string $offText = '';

    public function checked(bool $checked = true): static
    {
        $this->checked = $checked;
        return $this;
    }

    public function onText(string $text): static
    {
        $this->onText = $text;
        return $this;
    }

    public function offText(string $text): static
    {
        $this->offText = $text;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $switchLabelEl = Element::make('label')->class('ux-switch');

        $inputEl = Element::make('input')
            ->attr('type', 'checkbox')
            ->attr('name', $this->name)
            ->attr('value', '1')
            ->class('ux-switch-input');

        if ($this->checked) {
            $inputEl->attr('checked', '');
        }

        foreach ($this->buildFieldAttrs() as $key => $value) {
            if ($key !== 'name') {
                $inputEl->attr($key, $value);
            }
        }

        $switchLabelEl->child($inputEl);
        $switchLabelEl->child(Element::make('span')->class('ux-switch-slider'));
        $switchLabelEl->child(Element::make('span')->class('ux-switch-label-on')->text($this->onText ?: t('ux.on')));
        $switchLabelEl->child(Element::make('span')->class('ux-switch-label-off')->text($this->offText ?: t('ux.off')));

        $groupEl->child($switchLabelEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
