<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

class Input extends FormField
{
    protected string $type = 'text';

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function email(): static
    {
        return $this->type('email');
    }

    public function password(): static
    {
        return $this->type('password');
    }

    public function number(): static
    {
        return $this->type('number');
    }

    public function date(): static
    {
        return $this->type('date');
    }

    public function datetime(): static
    {
        return $this->type('datetime-local');
    }

    public function time(): static
    {
        return $this->type('time');
    }

    public function url(): static
    {
        return $this->type('url');
    }

    public function tel(): static
    {
        return $this->type('tel');
    }

    public function search(): static
    {
        return $this->type('search');
    }

    public function color(): static
    {
        return $this->type('color');
    }

    public function range(): static
    {
        return $this->type('range');
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $inputEl = Element::make('input')
            ->attr('type', $this->type)
            ->class('ux-form-input');

        foreach ($this->buildFieldAttrs() as $key => $value) {
            $inputEl->attr($key, $value);
        }

        if ($this->value !== null) {
            $inputEl->attr('value', (string)$this->value);
        }

        $groupEl->child($inputEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
